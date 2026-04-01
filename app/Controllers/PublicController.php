<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;

class PublicController
{
    public function home(Request $request, array $params = []): void
    {
        // If already logged in, go straight to dashboard
        if (!empty($_SESSION['user_id'])) {
            Response::redirect('/dashboard');
        }

        Response::render('public/home', [
            'title'  => 'Farm & Land Management',
            'layout' => 'public',
        ]);
    }

    public function privacy(Request $request, array $params = []): void
    {
        Response::render('public/privacy', [
            'title'  => 'Privacy Policy',
            'layout' => 'public',
        ]);
    }
}
