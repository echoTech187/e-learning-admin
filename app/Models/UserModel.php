<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id', 'name', 'email', 'password', 'role', 'photo',
        'phone', 'is_active', 'remember_token',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'name'  => 'required|min_length[3]|max_length[150]',
        'email' => 'required|valid_email|max_length[150]',
        'role'  => 'required|in_list[admin,instructor,student,parent,general,pending]',
    ];
    
    protected $validationMessages = [
        'name' => [
            'required' => 'Mohon isikan nama lengkap Anda.',
            'min_length' => 'Nama lengkap minimal harus terdiri dari 3 karakter.',
            'max_length' => 'Nama lengkap tidak boleh lebih dari 150 karakter.'
        ],
        'email' => [
            'required' => 'Mohon isikan alamat email Anda.',
            'valid_email' => 'Mohon isikan format alamat email yang valid (contoh: nama@email.com).',
            'max_length' => 'Alamat email tidak boleh lebih dari 150 karakter.'
        ],
        'role' => [
            'required' => 'Mohon pilih peran Anda (Siswa atau Orang Tua).',
            'in_list' => 'Peran yang dipilih tidak valid.'
        ]
    ];

    protected function generateUuid(array $data)
    {
        if (empty($data['data'][$this->primaryKey])) {
            $data['data'][$this->primaryKey] = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
        }
        return $data;
    }

    protected $beforeInsert = ['generateId'];

    protected function generateId(array $data)
    {
        if (empty($data['data']['id'])) {
            $data['data']['id'] = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
        }
        return $data;
    }
}