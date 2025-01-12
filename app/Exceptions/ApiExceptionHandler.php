<?php

namespace App\Exceptions;

use App\Traits\ApiResponses;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class ApiExceptionHandler
{
    use ApiResponses;

    public function handleException(Throwable $e, Request $request)
    {
        if ($e instanceof ValidationException) {
            return $this->handleValidationException($e);
        }

        if ($e instanceof ModelNotFoundException) {
            return $this->handleModelNotFoundException($e);
        }

        if ($e instanceof AuthenticationException) {
            return $this->handleAuthenticationException($e);
        }

        return $this->handleGenericException($e);
    }

    protected function handleValidationException(ValidationException $e)
    {
        $errors = [];
        foreach ($e->errors() as $key => $messages) {
            foreach ($messages as $message) {
                $errors[] = [
                    'status' => 422,
                    'message' => $message,
                    'source' => $key,
                ];
            }
        }
        return $this->error($errors, 422);
    }

    protected function handleModelNotFoundException(ModelNotFoundException $e)
    {
        return $this->error([
            [
                'status' => 404,
                'message' => 'The resource cannot be found.',
                'source' => $e->getModel(),
            ],
        ], 404);
    }

    protected function handleAuthenticationException(AuthenticationException $e)
    {
        return $this->error([
            [
                'status' => 401,
                'message' => 'Unauthenticated',
                'source' => '',
            ],
        ], 401);
    }

    protected function handleGenericException(Throwable $e)
    {
        return $this->error([
            [
                'type' => class_basename($e),
                'status' => 500,
                'message' => $e->getMessage(),
                'source' => 'Line: ' . $e->getLine() . ' in ' . $e->getFile(),
            ],
        ], 500);
    }
}
