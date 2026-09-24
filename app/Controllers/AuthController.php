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
            // Login riuscito
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'];

            if ($remember) {
                // Genera un token sicuro lungo 64 caratteri
                $token = bin2hex(random_bytes(32)); 
                $tokenHash = hash('sha256', $token);
                // Scadenza tra 1 anno
                $expiresAt = date('Y-m-d H:i:s', time() + (365 * 24 * 60 * 60)); 
                
                $this->userModel->storeRememberToken($user['id'], $tokenHash, $expiresAt);
                
                // Imposta il cookie in modo sicuro (HttpOnly)
                setcookie('remember_me', $token, time() + (365 * 24 * 60 * 60), '/', '', false, true);
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
        // Rimuovi sessione
        session_unset();
        session_destroy();

        // Rimuovi cookie
        if (isset($_COOKIE['remember_me'])) {
            setcookie('remember_me', '', time() - 3600, '/');
            // Nota: Andrebbe anche cancellato il token dal DB per sicurezza
        }

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
        if (empty($email) || empty($password) || empty($firstName)) {
            header('Location: ' . BASE_PATH . '/register?error=empty_fields');
            exit;
        }

        // Verifica se l'email esiste già
        if ($this->userModel->findByEmail($email)) {
            header('Location: ' . BASE_PATH . '/register?error=email_exists');
            exit;
        }

        // Crea l'utente
        $userId = $this->userModel->create([
            'email' => $email,
            'password' => $password,
            'first_name' => $firstName,
            'last_name' => $lastName
        ]);

        if ($userId) {
            // Autenticazione automatica dopo la registrazione
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $firstName;
            
            // CORREZIONE: Mandalo all'onboarding, non alla dashboard!
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
        
        // Dati in arrivo dal nuovo form Wizard o Manuale
        $uniId = (int)($_POST['university_id'] ?? 1);
        $courseName = trim($_POST['course_name'] ?? '');
        $sede = trim($_POST['sede'] ?? 'Non specificata');
        $anno = (int)($_POST['anno'] ?? 1);
        
        // Se l'utente usa il Wizard, il JS invia "AUTO" nei codici. 
        // Se usa il manuale (Step 4), invia i codici reali.
        $linkId = trim($_POST['link_calendario_id'] ?? '');
        $clienteId = trim($_POST['cliente_id'] ?? '');

        // Creiamo il JSON di configurazione
        $configJson = json_encode([
            'linkCalendarioId' => $linkId,
            'clienteId' => $clienteId
        ]);

        (new \App\Models\UserModel())->saveAcademicProfile($userId, $uniId, $courseName, $sede, $anno, $configJson);

        header('Location: ' . BASE_PATH . '/dashboard');
        exit;
    }
}