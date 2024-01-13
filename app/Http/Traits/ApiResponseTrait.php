<?php

namespace App\Http\Traits;

use App\Http\Resources\EmptyResource;
use App\Http\Resources\EmptyResourceCollection;
use Error;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Validation\ValidationException;

// code from https://dev.to/bawa_geek/laravel-api-the-best-way-to-return-the-uniform-response-for-all-type-of-requests-2ao1
trait ApiResponseTrait
{
    /**
     * @param  int  $statusCode
     * @param  array  $headers
     * @return JsonResponse
     */
    protected function respondWithResource(JsonResource $resource, $message = null, $statusCode = 200, $headers = [])
    {
        // https://laracasts.com/discuss/channels/laravel/pagination-data-missing-from-api-resource

        return $this->apiResponse(
            [
                'success' => true,
                'result' => $resource,
                'message' => $message,
            ],
            $statusCode,
            $headers
        );
    }

    /**
     * @param  array  $data
     * @param  int  $statusCode
     * @param  array  $headers
     * @return array
     */
    public function parseGivenData($data = [], $statusCode = 200, $headers = [])
    {
        $responseStructure = [
            'success' => $data['success'],
            'message' => $data['message'] ?? null,
            'result' => $data['result'] ?? null,
        ];
        if (isset($data['errors'])) {
            $responseStructure['errors'] = $data['errors'];
        }
        if (isset($data['status'])) {
            $statusCode = $data['status'];
        }

        if (isset($data['exception']) && ($data['exception'] instanceof Error || $data['exception'] instanceof Exception)) {
            if (config('app.env') !== 'production') {
                $responseStructure['exception'] = [
                    'message' => $data['exception']->getMessage(),
                    'file' => $data['exception']->getFile(),
                    'line' => $data['exception']->getLine(),
                    'code' => $data['exception']->getCode(),
                    'trace' => $data['exception']->getTrace(),
                ];
            }

            if ($statusCode === 200) {
                $statusCode = 500;
            }
        }
        if ($data['success'] === false) {
            if (isset($data['error_code'])) {
                $responseStructure['error_code'] = $data['error_code'];
            } else {
                $responseStructure['error_code'] = 1;
            }
        }

        return ['content' => $responseStructure, 'statusCode' => $statusCode, 'headers' => $headers];
    }

    /*
     *
     * Just a wrapper to facilitate abstract
     */

    /**
     * Return generic json response with the given data.
     *
     * @param  int  $statusCode
     * @param  array  $headers
     * @return JsonResponse
     */
    protected function apiResponse($data = [], $statusCode = 200, $headers = [])
    {
        // https://laracasts.com/discuss/channels/laravel/pagination-data-missing-from-api-resource

        $result = $this->parseGivenData($data, $statusCode, $headers);

        return response()->json(
            $result['content'],
            $result['statusCode'],
            $result['headers']
        );
    }

    /*
     *
     * Just a wrapper to facilitate abstract
     */

    /**
     * @param  int  $statusCode
     * @param  array  $headers
     * @return JsonResponse
     */
    protected function respondWithResourceCollection(ResourceCollection $resourceCollection, $message = null, $statusCode = 200, $headers = [])
    {
        // https://laracasts.com/discuss/channels/laravel/pagination-data-missing-from-api-resource

        return $this->apiResponse(
            [
                'success' => true,
                'result' => $resourceCollection->response()->getData(),
            ],
            $statusCode,
            $headers
        );
    }

    /**
     * Respond with success.
     *
     * @param  string  $message
     * @return JsonResponse
     */
    protected function respondSuccess($message = '')
    {
        return $this->apiResponse(['success' => true, 'message' => $message]);
    }

    /**
     * Respond with created.
     *
     * @return JsonResponse
     */
    protected function respondCreated($data)
    {
        return $this->apiResponse($data, 201);
    }

    /**
     * Respond with no content.
     *
     * @param  string  $message
     * @return JsonResponse
     */
    protected function respondNoContent($message = 'No Content Found')
    {
        return $this->apiResponse(['success' => false, 'message' => $message], 200);
    }

    /**
     * Respond with no content.
     *
     * @param  string  $message
     * @return JsonResponse
     */
    protected function respondNoContentResource($message = 'No Content Found')
    {
        return $this->respondWithResource(new EmptyResource([]), $message);
    }

    /**
     * Respond with no content.
     *
     * @param  string  $message
     * @return JsonResponse
     */
    protected function respondNoContentResourceCollection($message = 'No Content Found')
    {
        return $this->respondWithResourceCollection(new EmptyResourceCollection([]), $message);
    }

    /**
     * Respond with unauthorized.
     *
     * @param  string  $message
     * @return JsonResponse
     */
    protected function respondUnAuthorized($message = 'Unauthorized')
    {
        return $this->respondError($message, 401, null, 401);
    }

    /**
     * Respond with error.
     *
     * @param  bool|null  $error_code
     * @return JsonResponse
     */
    protected function respondError($message, int $statusCode = 400, Exception $exception = null, int $error_code = 1)
    {
        return $this->apiResponse(
            [
                'success' => false,
                'message' => $message ?? 'There was an internal error, Pls try again later',
                'exception' => $exception,
                'error_code' => $error_code,
            ],
            $statusCode
        );
    }

    /**
     * Respond with forbidden.
     *
     * @param  string  $message
     * @return JsonResponse
     */
    protected function respondForbidden($message = 'Forbidden')
    {
        return $this->respondError($message, 403, null, 403);
    }

    /**
     * Respond with not found.
     *
     * @param  string  $message
     * @return JsonResponse
     */
    protected function respondNotFound($message = 'Not Found')
    {
        return $this->respondError($message, 404, null, 404);
    }

    /**
     * Respond with Duplicate record
     *
     * @param  string  $message
     * @return JsonResponse
     */
    protected function respondErrorDuplicate($message = 'Duplicate record')
    {
        return $this->respondError($message, 409, null, 409);
    }

    /**
     * Respond with internal error.
     *
     * @param  string  $message
     * @return JsonResponse
     */
    protected function respondInternalError($message = 'Internal Error')
    {
        return $this->respondError($message, 500, null, 500);
    }

    protected function respondValidationErrors(ValidationException $exception)
    {
        return $this->apiResponse(
            [
                'success' => false,
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ],
            422
        );
    }
}
