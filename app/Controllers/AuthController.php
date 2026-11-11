<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http;
use App\Core\RateLimiter;
use App\Core\Str;
use App\Core\View;
use App\Models\UserModel;

class AuthController
{
    // Hash bcrypt costante per uniformare il tempo di verifica quando l'utente non esiste
    private const DUMMY_PASSWORD_HASH = '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';

    private UserModel $userModel;
    private RateLimiter $rateLimiter;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->rateLimiter = new RateLimiter();
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
            header('Location: ' . BASE_PATH . '/dashboard');
            exit;
        }

        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $remember = isset($_POST['remember']);

        $ip = Http::clientIp();
        $emailBucketId = hash('sha256', strtolower($email));

        if (
            $this->rateLimiter->tooManyAttempts('login:ip', $ip, 5, 900)
            || ($email !== '' && $this->rateLimiter->tooManyAttempts('login:email', $emailBucketId, 5, 900))
        ) {
            http_response_code(429);
            header('Location: ' . BASE_PATH . '/login?error=ratelimit');
            exit;
        }

        $user = $email !== '' ? $this->userModel->findAuthByEmail($email) : null;
        $hashToVerify = $user['password_hash'] ?? self::DUMMY_PASSWORD_HASH;
        $passwordValid = password_verify($password, (string)$hashToVerify);

        if ($user && $passwordValid) {
            $this->rateLimiter->clear('login:email', $emailBucketId);

            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'];

            $db = \App\Core\Database::getInstance();
            $stmt = $db->prepare("SELECT theme FROM user_preferences WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $_SESSION['theme'] = $stmt->fetchColumn() ?: 'light';

            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $expiresAt = date('Y-m-d H:i:s', time() + (365 * 24 * 60 * 60));

                $this->userModel->storeRememberToken((int)$user['id'], $tokenHash, $expiresAt);

                setcookie('remember_me', $token, [
                    'expires'  => time() + (365 * 24 * 60 * 60),
                    'path'     => '/',
                    'secure'   => $this->isHttps(),
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }

            if (!empty($_SESSION['pending_invite_code'])) {
                $code = $_SESSION['pending_invite_code'];
                unset($_SESSION['pending_invite_code']);
                header('Location: ' . BASE_PATH . '/invito/' . urlencode((string)$code));
                exit;
            }

            header('Location: ' . BASE_PATH . '/dashboard');
            exit;
        }

        $this->rateLimiter->hit('login:ip', $ip);
        if ($email !== '') {
            $this->rateLimiter->hit('login:email', $emailBucketId);
        }

        header('Location: ' . BASE_PATH . '/login?error=1');
        exit;
    }

    public function logout(): void
    {
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

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie((string)session_name(), '', [
                'expires'  => time() - 3600,
                'path'     => $params['path'] ?? '/',
                'domain'   => $params['domain'] ?? '',
                'secure'   => (bool)($params['secure'] ?? $this->isHttps()),
                'httponly' => (bool)($params['httponly'] ?? true),
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();

        header('Location: ' . BASE_PATH . '/');
        exit;
    }

    public function showRegister(): void
    {
        View::render('auth/register', ['pageTitle' => 'Crea Account - Campusly']);
    }

    public function processRegister(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_PATH . '/register');
            exit;
        }

        $ip = Http::clientIp();
        if ($this->rateLimiter->tooManyAttempts('register:ip', $ip, 5, 3600)) {
            http_response_code(429);
            header('Location: ' . BASE_PATH . '/register?error=ratelimit');
            exit;
        }

        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $firstName = trim((string)($_POST['first_name'] ?? ''));
        $lastName = trim((string)($_POST['last_name'] ?? ''));

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

        $firstName = Str::cut($firstName, 100);
        $lastName = Str::cut($lastName, 100);

        $this->rateLimiter->hit('register:ip', $ip);

        if ($this->userModel->findByEmail($email)) {
            header('Location: ' . BASE_PATH . '/register?error=email_exists');
            exit;
        }

        try {
            $userId = $this->userModel->create([
                'email'      => $email,
                'password'   => $password,
                'first_name' => $firstName,
                'last_name'  => $lastName
            ]);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                header('Location: ' . BASE_PATH . '/register?error=email_exists');
                exit;
            }
            throw $e;
        }

        if ($userId) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $firstName;

            header('Location: ' . BASE_PATH . '/onboarding');
            exit;
        }

        header('Location: ' . BASE_PATH . '/register?error=server');
        exit;
    }

    public function showForgotPassword(): void
    {
        View::render('auth/forgot', ['pageTitle' => 'Recupera Password - Campusly']);
    }

    public function processForgotPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_PATH . '/password-dimenticata');
            exit;
        }

        $ip = Http::clientIp();
        $email = trim((string)($_POST['email'] ?? ''));
        $emailBucket = hash('sha256', strtolower($email));

        if (
            $this->rateLimiter->tooManyAttempts('forgot:ip', $ip, 5, 900)
            || ($email !== '' && $this->rateLimiter->tooManyAttempts('forgot:email', $emailBucket, 3, 900))
        ) {
            header('Location: ' . BASE_PATH . '/password-dimenticata?status=sent');
            exit;
        }

        $this->rateLimiter->hit('forgot:ip', $ip);
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->rateLimiter->hit('forgot:email', $emailBucket);
            $user = $this->userModel->findByEmail($email);

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $expiresAt = date('Y-m-d H:i:s', time() + 3600);

                $this->userModel->createPasswordReset((int)$user['id'], $tokenHash, $expiresAt);

                $appConfig = require BASEPATH . '/config/app.php';
                $from = str_replace(["\r", "\n"], '', (string)($appConfig['mail']['from'] ?? 'noreply@campusly.it'));
                $scheme = $this->isHttps() ? 'https' : 'http';
                $host = preg_replace('/[^a-zA-Z0-9.\-:]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
                $resetUrl = $scheme . '://' . $host . BASE_PATH . '/reset-password/' . $token;

                $subject = 'Campusly - Reimposta la tua password';
                $message = "Ciao " . $user['first_name'] . ",\n\n"
                         . "Hai richiesto di reimpostare la password del tuo account Campusly.\n"
                         . "Usa il seguente link entro 1 ora:\n\n"
                         . $resetUrl . "\n\n"
                         . "Se non hai richiesto tu il reset, ignora questa email.\n";

                $headers = "From: {$from}\r\nReply-To: {$from}\r\nContent-Type: text/plain; charset=UTF-8\r\n";
                @mail($user['email'], $subject, $message, $headers);
            }
        }

        // Risposta sempre uniforme per non rivelare se l'email è registrata
        header('Location: ' . BASE_PATH . '/password-dimenticata?status=sent');
        exit;
    }

    public function showResetPassword(string $token): void
    {
        $cleanToken = preg_replace('/[^a-f0-9]/', '', strtolower($token));
        $reset = strlen($cleanToken) === 64
            ? $this->userModel->findValidPasswordReset(hash('sha256', $cleanToken))
            : null;

        View::render('auth/reset', [
            'pageTitle' => 'Nuova Password - Campusly',
            'token'     => $cleanToken,
            'isValid'   => $reset !== null,
        ]);
    }

    public function processResetPassword(string $token): void
    {
        $cleanToken = preg_replace('/[^a-f0-9]/', '', strtolower($token));
        if (strlen($cleanToken) !== 64) {
            header('Location: ' . BASE_PATH . '/password-dimenticata');
            exit;
        }

        $reset = $this->userModel->findValidPasswordReset(hash('sha256', $cleanToken));
        if (!$reset) {
            header('Location: ' . BASE_PATH . '/reset-password/' . $cleanToken . '?error=expired');
            exit;
        }

        $password = (string)($_POST['password'] ?? '');
        if (strlen($password) < 8) {
            header('Location: ' . BASE_PATH . '/reset-password/' . $cleanToken . '?error=weak_password');
            exit;
        }

        $this->userModel->consumePasswordReset((int)$reset['id'], (int)$reset['user_id'], $password);

        header('Location: ' . BASE_PATH . '/login?reset=success');
        exit;
    }

    public function showOnboarding(): void
    {
        if (!isset($_GET['add'])) {
            $courses = $this->userModel->getUserCourses((int)$_SESSION['user_id']);
            if (!empty($courses)) {
                header('Location: ' . BASE_PATH . '/dashboard');
                exit;
            }
        }

        $db = \App\Core\Database::getInstance();
        $stmt = $db->query("SELECT id, name, logo_path FROM universities WHERE is_active = 1 ORDER BY name ASC");
        $universities = $stmt->fetchAll();

        View::render('pages/onboarding', [
            'pageTitle'    => 'Configurazione - Campusly',
            'pageCss'      => 'onboarding',
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
        $sede = Str::cut($sede, 100);

        if ($uniId <= 0 || $courseName === '' || $anno < 1 || $anno > 8) {
            header('Location: ' . BASE_PATH . '/onboarding?add=1&error=invalid');
            exit;
        }

        $saved = $this->userModel->saveAcademicProfile($userId, $uniId, $courseName, $sede, $anno);

        if (!$saved) {
            header('Location: ' . BASE_PATH . '/onboarding?add=1&error=invalid');
            exit;
        }

        if (!empty($_SESSION['pending_invite_code'])) {
            $code = $_SESSION['pending_invite_code'];
            unset($_SESSION['pending_invite_code']);
            header('Location: ' . BASE_PATH . '/invito/' . urlencode((string)$code));
            exit;
        }

        header('Location: ' . BASE_PATH . '/dashboard');
        exit;
    }
}
