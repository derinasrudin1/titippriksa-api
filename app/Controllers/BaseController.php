<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    protected $request;
    protected $helpers = [];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
    }

    protected function apiResponse($status, $code, $message, $data = null)
    {
        $responsePayload = [
            'status' => $status,
            'code' => (string) $code,
            'message' => $message,
        ];

        if ($data !== null) {
            $responsePayload['data'] = $data;
        }

        return $this->response->setStatusCode($code)->setJSON($responsePayload);
    }
}