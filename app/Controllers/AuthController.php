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
            header('Location: /login');
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

            header('Location: /dashboard');
            exit;
        }

        // Login fallito (da gestire poi con messaggi di errore nella View)
        header('Location: /login?error=1');
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

        header('Location: /');
        exit;
    }
}