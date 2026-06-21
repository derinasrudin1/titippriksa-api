<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class Cors implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Mengizinkan semua domain (*) untuk mengakses API ini
        header('Access-Control-Allow-Origin: *');

        // Mengizinkan header tertentu (termasuk Authorization untuk token JWT nanti)
        header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');

        // Mengizinkan metode HTTP yang dipakai
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');

        // Menangani "Preflight Request"
        // Browser selalu mengirim request OPTIONS dulu sebelum POST/GET untuk mengecek CORS
        $method = $_SERVER['REQUEST_METHOD'] ?? $request->getMethod(true);
        if ($method === 'OPTIONS') {
            // Hentikan eksekusi dan langsung kembalikan status OK (200) agar browser lega
            die();
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak ada aksi setelah response
    }
}