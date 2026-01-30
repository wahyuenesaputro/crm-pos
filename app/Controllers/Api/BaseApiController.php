<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Libraries\JWTHandler;

class BaseApiController extends ResourceController
{
    use ResponseTrait;

    protected $format = 'json';
    protected ?object $currentUser = null;
    protected ?int $tenantId = null;
    protected ?int $branchId = null;

    public function __construct()
    {
        $jwt = new JWTHandler();
        $this->currentUser = $jwt->getUserFromToken();
        if ($this->currentUser) {
            $this->tenantId = $this->currentUser->tenant_id ?? null;
            $this->branchId = $this->currentUser->branch_id ?? null;
        }
    }

    /**
     * Standard success response
     */
    protected function success($data = null, string $message = 'Success', int $code = 200)
    {
        return $this->respond([
            'status' => $code,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Standard error response
     */
    protected function error(string $message, int $code = 400, $errors = null)
    {
        $response = [
            'status' => $code,
            'error' => $this->getStatusMessage($code),
            'message' => $message
        ];
        if ($errors) {
            $response['errors'] = $errors;
        }
        return $this->respond($response, $code);
    }

    /**
     * Paginated response
     */
    protected function paginate($data, int $total, int $page, int $limit)
    {
        return $this->respond([
            'status' => 200,
            'data' => $data,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    }

    private function getStatusMessage(int $code): string
    {
        $messages = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            422 => 'Validation Error',
            500 => 'Internal Server Error'
        ];
        return $messages[$code] ?? 'Error';
    }

    /**
     * Handle OPTIONS preflight request
     */
    public function options()
    {
        return $this->response->setStatusCode(200);
    }
}
