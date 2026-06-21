<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\UserModel;
use Firebase\JWT\JWT;

class AuthController extends BaseController
{
    public function register()
    {
        $rules = [
            'nis' => 'required|numeric|is_unique[users.nis]',
            'name' => 'required|min_length[3]',
            'password' => 'required|min_length[6]',
            'role' => 'permit_empty|in_list[student,teacher,admin]'
        ];

        if (!$this->validate($rules)) {
            return $this->apiResponse('error', 400, 'Validasi gagal', $this->validator->getErrors());
        }

        $userModel = new UserModel();

        $data = [
            'nis' => $this->request->getVar('nis'),
            'name' => $this->request->getVar('name'),
            'password' => password_hash($this->request->getVar('password'), PASSWORD_BCRYPT),
            'role' => $this->request->getVar('role') ?? 'student',
        ];

        $userModel->save($data);

        // Ambil data user yang baru saja dibuat untuk dimasukkan ke response
        $newUser = $userModel->where('nis', $data['nis'])->first();

        return $this->apiResponse('success', 201, 'Registrasi berhasil', [
            'id' => $newUser['id'],
            'nis' => (int) $newUser['nis'],
            'name' => $newUser['name'],
            'role' => $newUser['role'],
            'created_at' => $newUser['created_at']
        ]);
    }

    public function login()
    {
        $rules = [
            'nis' => 'required|numeric',
            'password' => 'required'
        ];

        if (!$this->validate($rules)) {
            return $this->apiResponse('error', 400, 'Validasi gagal', $this->validator->getErrors());
        }

        $userModel = new UserModel();
        $user = $userModel->where('nis', $this->request->getVar('nis'))->first();

        if (!$user) {
            return $this->apiResponse('error', 404, 'NIS tidak terdaftar.');
        }

        if (!password_verify($this->request->getVar('password'), $user['password'])) {
            return $this->apiResponse('error', 401, 'Password salah.');
        }

        $secretKey = env('JWT_SECRET_KEY');
        $currentTime = time();

        $payload = [
            'iss' => 'ExamAppBackend',
            'aud' => 'ExamAppFlutter',
            'iat' => $currentTime,
            'exp' => $currentTime + (60 * 60 * 24),
            'uid' => $user['id'],
            'role' => $user['role']
        ];

        $token = JWT::encode($payload, $secretKey, 'HS256');

        return $this->apiResponse('success', 200, 'Login berhasil', [
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'nis' => (int) $user['nis'],
                'name' => $user['name'],
                'role' => $user['role']
            ]
        ]);
    }
}