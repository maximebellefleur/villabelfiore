<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;
use App\Support\CSRF;

class AuthController
{
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

        $email    = trim((string) $request->post('email', ''));
        $password = (string) $request->post('password', '');

        $errors = [];
        if (empty($email)) {
            $errors['email'] = 'Email is required.';
        }
        if (empty($password)) {
            $errors['password'] = 'Password is required.';
        }

        if (!empty($errors)) {
            flash('errors', $errors);
            flash('old', ['email' => $email]);
            Response::redirect('/login');
        }

        $db   = \App\Support\DB::getInstance();
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

        Response::redirect('/dashboard');
    }

    public function logout(Request $request, array $params = []): void
    {
        CSRF::validate($request->post('_token', ''));
        $_SESSION = [];
        session_destroy();
        Response::redirect('/login');
    }
}
