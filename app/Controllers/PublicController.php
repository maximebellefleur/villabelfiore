<?php

namespace App\Controllers;

use App\Support\Request;
use App\Support\Response;

class PublicController
{
    public function privacy(Request $request, array $params = []): void
    {
        Response::render('public/privacy', [
            'title'  => 'Privacy Policy',
            'layout' => 'public',
        ]);
    }
}
