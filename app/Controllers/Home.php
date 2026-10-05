<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        // Redirect root to login page if not logged in, otherwise to admin dashboard
        if (session()->get('is_logged_in')) {
            return redirect()->to('/admin');
        }
        return redirect()->to('/auth/login');
    }
}
