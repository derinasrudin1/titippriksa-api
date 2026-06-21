<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JWTAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $header = $request->getHeaderLine('Authorization');
        $token = null;

        // Ekstrak token dari header "Bearer <token>"
        if (!empty($header)) {
            if (preg_match('/Bearer\s(\S+)/', $header, $matches)) {
                $token = $matches[1];
            }
        }

        // Jika token tidak ada sama sekali
        if (is_null($token) || empty($token)) {
            $response = service('response');
            return $response->setJSON([
                'status' => 'error',
                'code' => '401',
                'message' => 'Akses ditolak. Token tidak ditemukan di header.'
            ])->setStatusCode(401);
        }

        try {
            $secretKey = env('JWT_SECRET_KEY');
            // Decode token. Jika token expired atau dimanipulasi, akan otomatis masuk ke catch
            $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));

            // Memeriksa Hak Akses (Role) jika ada argumen yang dikirim di Routes
            // Contoh di routes: 'filter' => 'jwt:teacher,admin'
            if ($arguments) {
                $userRole = $decoded->role;
                if (!in_array($userRole, $arguments)) {
                    $response = service('response');
                    return $response->setJSON([
                        'status' => 'error',
                        'code' => '403',
                        'message' => 'Akses ditolak. Anda tidak memiliki hak akses (role) untuk fitur ini.'
                    ])->setStatusCode(403);
                }
            }

            // Menyisipkan data user dari token ke dalam request agar bisa dipakai di Controller
            $request->user = $decoded;

        } catch (Exception $e) {
            $response = service('response');
            return $response->setJSON([
                'status' => 'error',
                'code' => '401',
                'message' => 'Token tidak valid atau sudah kadaluarsa.',
                'error' => $e->getMessage()
            ])->setStatusCode(401);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}