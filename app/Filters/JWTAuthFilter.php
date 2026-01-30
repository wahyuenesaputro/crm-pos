<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\JWTHandler;

class JWTAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $jwt = new JWTHandler();
        $token = $jwt->getTokenFromHeader();

        if (!$token) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status' => 401,
                    'error' => 'Unauthorized',
                    'message' => 'Access token is missing'
                ]);
        }

        $decoded = $jwt->validateToken($token);
        if (!$decoded) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status' => 401,
                    'error' => 'Unauthorized',
                    'message' => 'Invalid or expired token'
                ]);
        }

        // Store user data in request for later use
        $request->user = $decoded->data ?? null;

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
