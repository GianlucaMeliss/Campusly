<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\UserModel;

class AuthController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    private function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    }

    public function showLogin(): void
    {
        View::render('auth/login', ['pageTitle' => 'Accedi a Campusly']);
    }

    public function processLogin(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_PATH . '/login');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        $user = $this->userModel->findByEmail($email);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Login riuscito: nuovo ID di sessione contro la session fixation
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'];

            $db = \App\Core\Database::getInstance();
            $stmt = $db->prepare("SELECT theme FROM user_preferences WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $_SESSION['theme'] = $stmt->fetchColumn() ?: 'light';

            if ($remember) {
                // Genera un token sicuro lungo 64 caratteri
                $token = bin2hex(random_bytes(32)); 
                $tokenHash = hash('sha256', $token);
                // Scadenza tra 1 anno
                $expiresAt = date('Y-m-d H:i:s', time() + (365 * 24 * 60 * 60)); 
                
                $this->userModel->storeRememberToken($user['id'], $tokenHash, $expiresAt);
                
                // Imposta il cookie in modo sicuro (HttpOnly)
                setcookie('remember_me', $token, [
                    'expires'  => time() + (365 * 24 * 60 * 60),
                    'path'     => '/',
                    'secure'   => $this->isHttps(),
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }

            header('Location: ' . BASE_PATH . '/dashboard');
            exit;
        }

        // Login fallito (da gestire poi con messaggi di errore nella View)
        header('Location: ' . BASE_PATH . '/login?error=1');
        exit;
    }

    public function logout(): void
    {
        // Invalida il token "ricordami" anche lato database
        if (isset($_COOKIE['remember_me']) && is_string($_COOKIE['remember_me'])) {
            $this->userModel->deleteToken(hash('sha256', $_COOKIE['remember_me']));
            setcookie('remember_me', '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'secure'   => $this->isHttps(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        // Rimuovi sessione
        session_unset();
        session_destroy();

        header('Location: ' . BASE_PATH . '/');
        exit;
    }

    public function showRegister(): void
    {
        \App\Core\View::render('auth/register', ['pageTitle' => 'Crea Account - Campusly']);
    }

    public function processRegister(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_PATH . '/register');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');

        // Validazione base
        if ($email === '' || $password === '' || $firstName === '') {
            header('Location: ' . BASE_PATH . '/register?error=empty_fields');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            header('Location: ' . BASE_PATH . '/register?error=invalid_email');
            exit;
        }

        if (strlen($password) < 8) {
            header('Location: ' . BASE_PATH . '/register?error=weak_password');
            exit;
        }

        $firstName = \App\Core\Str::cut($firstName, 100);
        $lastName = \App\Core\Str::cut($lastName, 100);

        // Verifica se l'email esiste già
        if ($this->userModel->findByEmail($email)) {
            header('Location: ' . BASE_PATH . '/register?error=email_exists');
            exit;
        }

        // Crea l'utente (la UNIQUE su email protegge anche dalla doppia richiesta simultanea)
        try {
            $userId = $this->userModel->create([
                'email' => $email,
                'password' => $password,
                'first_name' => $firstName,
                'last_name' => $lastName
            ]);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                header('Location: ' . BASE_PATH . '/register?error=email_exists');
                exit;
            }
            throw $e;
        }

        if ($userId) {
            // Autenticazione automatica dopo la registrazione
            session_regenerate_id(true);
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $firstName;

            // Mandalo all'onboarding, non alla dashboard
            header('Location: ' . BASE_PATH . '/onboarding');
            exit;
        }

        header('Location: ' . BASE_PATH . '/register?error=server');
        exit;
    }

    public function showOnboarding(): void
    {
        // Se NON c'è il parametro ?add=1, controlliamo se ha già dei corsi per mandarlo alla dashboard
        if (!isset($_GET['add'])) {
            $courses = (new \App\Models\UserModel())->getUserCourses((int)$_SESSION['user_id']);
            if (!empty($courses)) {
                header('Location: ' . BASE_PATH . '/dashboard');
                exit;
            }
        }

        $db = \App\Core\Database::getInstance();
        $stmt = $db->query("SELECT id, name, logo_path FROM universities WHERE is_active = 1 ORDER BY name ASC");
        $universities = $stmt->fetchAll();

        \App\Core\View::render('pages/onboarding', [
            'pageTitle' => 'Configurazione - Campusly',
            'pageCss' => 'onboarding',
            'universities' => $universities
        ]);
    }

    public function processOnboarding(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

        $userId = (int)$_SESSION['user_id'];

        $uniId = (int)($_POST['university_id'] ?? 0);
        $courseName = trim((string)($_POST['course_name'] ?? ''));
        $sede = trim((string)($_POST['sede'] ?? ''));
        $anno = (int)($_POST['anno'] ?? 0);

        if ($sede === '') {
            $sede = 'Non specificata';
        }
        $sede = \App\Core\Str::cut($sede, 100);

        if ($uniId <= 0 || $courseName === '' || $anno < 1 || $anno > 8) {
            header('Location: ' . BASE_PATH . '/onboarding?add=1&error=invalid');
            exit;
        }

        // I codici del calendario NON arrivano più dal client: li gestisce solo l'admin.
        $saved = (new \App\Models\UserModel())->saveAcademicProfile($userId, $uniId, $courseName, $sede, $anno);

        if (!$saved) {
            header('Location: ' . BASE_PATH . '/onboarding?add=1&error=invalid');
            exit;
        }

        header('Location: ' . BASE_PATH . '/dashboard');
        exit;
    }
}