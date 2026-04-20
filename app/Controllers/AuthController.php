<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;
use App\Support\CSRF;
use App\Support\DB;

class AuthController
{
    private function ensureRememberTable(DB $db): void
    {
        $db->execute("CREATE TABLE IF NOT EXISTS remember_tokens (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            user_id     INT NOT NULL,
            token_hash  VARCHAR(64) NOT NULL,
            expires_at  DATETIME NOT NULL,
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY  uk_token_hash (token_hash),
            KEY         idx_user_id (user_id)
        )");
    }

    public function showLogin(Request $request, array $params = []): void
    {
        if (isset($_SESSION['user_id'])) {
            Response::redirect('/dashboard');
        }
        Response::render('auth/login', ['title' => 'Sign In']);
    }

    public function login(Request $request, array $params = []): void
    {
        if (isset($_SESSION['user_id'])) {
            Response::redirect('/dashboard');
        }

        CSRF::validate($request->post('_token', ''));

        $email      = trim((string) $request->post('email', ''));
        $password   = (string) $request->post('password', '');
        $rememberMe = !empty($request->post('remember_me'));

        $errors = [];
        if (empty($email))    $errors['email']    = 'Email is required.';
        if (empty($password)) $errors['password'] = 'Password is required.';

        if (!empty($errors)) {
            flash('errors', $errors);
            flash('old', ['email' => $email]);
            Response::redirect('/login');
        }

        $db   = DB::getInstance();
        $user = $db->fetchOne('SELECT * FROM users WHERE email = ? AND is_active = 1', [$email]);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            flash('error', 'Invalid email or password.');
            flash('old', ['email' => $email]);
            Response::redirect('/login');
        }

        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['display_name'];

        $db->execute('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$user['id']]);

        if ($rememberMe) {
            $this->ensureRememberTable($db);
            $raw     = bin2hex(random_bytes(32));
            $hash    = hash('sha256', $raw);
            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
            $db->execute(
                'DELETE FROM remember_tokens WHERE user_id = ?',
                [$user['id']]
            );
            $db->execute(
                'INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (?,?,?)',
                [$user['id'], $hash, $expires]
            );
            setcookie('rooted_remember', $raw, [
                'expires'  => strtotime('+30 days'),
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        Response::redirect('/dashboard');
    }

    public function logout(Request $request, array $params = []): void
    {
        CSRF::validate($request->post('_token', ''));

        if (!empty($_SESSION['user_id']) && !empty($_COOKIE['rooted_remember'])) {
            try {
                $db   = DB::getInstance();
                $hash = hash('sha256', $_COOKIE['rooted_remember']);
                $db->execute('DELETE FROM remember_tokens WHERE token_hash = ?', [$hash]);
            } catch (\Throwable $e) {}
        }

        setcookie('rooted_remember', '', ['expires' => 1, 'path' => '/']);
        $_SESSION = [];
        session_destroy();
        Response::redirect('/login');
    }
}
