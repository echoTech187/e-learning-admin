<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;

class Register extends BaseController
{
    public function index()
    {
        // Admin registration is disabled (offline only)
        return redirect()->to('/auth/login')->with('error', 'Pendaftaran Admin hanya dapat dilakukan oleh Super Admin.');
    }

    public function process()
    {
        return redirect()->to('/auth/login')->with('error', 'Pendaftaran Admin dinonaktifkan.');
    }
}

