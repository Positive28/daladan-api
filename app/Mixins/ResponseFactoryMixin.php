<?php 

namespace App\Mixins;

use Illuminate\Http\JsonResponse;

class ResponseFactoryMixin 
{
    public function successJson()
    {
        return function ($data, int $status = 200): JsonResponse {
            return new JsonResponse([
                'success' => true,
                'data'    => $data,
                'message' => 'ok',
            ], $status);
        };
    }

    public function errorJson()
    {
        return function($message, $status, $errors = null, $data = null){
            $data = [
                'success' => false,
                'message' => $message,
                'errors' => $errors,
                'data' => $data
            ];
            return new JsonResponse($data, $status);
        };
    }
}