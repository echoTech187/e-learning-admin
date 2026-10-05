<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\AdminModel;

class Login extends BaseController
{
    protected AdminModel $adminModel;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->adminModel = new AdminModel();
    }

    public function index()
    {
        if (session()->get('is_logged_in')) {
            return redirect()->to('/admin');
        }

        return view('auth/login', [
            'title' => 'Portal Admin EduNusa',
            'meta_desc' => 'Login ke portal manajemen EduNusa.',
        ]);
    }

    public function process()
    {
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        $user = $this->adminModel->where('email', $email)->first();

        if (!$user || !password_verify($password, $user['password'])) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Email atau password salah. Silakan coba lagi.');
        }

        if (!$user['is_active']) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Akun Admin dinonaktifkan.');
        }

        session()->set([
            'user_id'      => $user['id'],
            'user_name'    => $user['name'],
            'user_email'   => $user['email'],
            'user_role'    => $user['role'] ?? 'superadmin',
            'is_logged_in' => true
        ]);

        return redirect()->to('/admin');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/masuk')->with('success', 'Anda telah berhasil keluar.');
    }
}


