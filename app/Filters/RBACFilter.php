<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\JWTHandler;

class RBACFilter implements FilterInterface
{
    /**
     * Check if user has required role/permission
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $jwt = new JWTHandler();
        $user = $jwt->getUserFromToken();

        if (!$user) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status' => 401,
                    'error' => 'Unauthorized',
                    'message' => 'Authentication required'
                ]);
        }

        // If no specific roles required, just check authentication
        if (empty($arguments)) {
            return $request;
        }

        $userRoles = $user->roles ?? [];

        // Check if user has any of the required roles
        foreach ($arguments as $requiredRole) {
            if (in_array($requiredRole, $userRoles)) {
                return $request;
            }
        }

        return service('response')
            ->setStatusCode(403)
            ->setJSON([
                'status' => 403,
                'error' => 'Forbidden',
                'message' => 'Insufficient permissions'
            ]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
