<?php

namespace Sevming\LaravelResponse;

use Illuminate\Support\Arr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\{ResourceCollection, JsonResource};
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Contracts\Support\Arrayable;

class Response
{
    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_ACCEPTED = 202;
    public const HTTP_NO_CONTENT = 204;
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_METHOD_NOT_ALLOWED = 405;
    public const HTTP_UNPROCESSABLE_ENTITY = 422;
    public const HTTP_INTERNAL_SERVER_ERROR = 500;

    /**
     * Return an success response.
     *
     * @param mixed  $data
     * @param string $message
     * @param int    $code
     * @param array  $headers
     * @param int    $option
     *
     * @return JsonResponse
     */
    public function success(
        $data = null,
        string $message = '',
        int $code = self::HTTP_OK,
        array $headers = [],
        int $option = 0
    ) {
        if ($data instanceof ResourceCollection) {
            return $this->formatResourceCollectionResponse(...func_get_args());
        }

        if ($data instanceof AbstractPaginator) {
            return $this->formatPaginatedResponse(...func_get_args());
        }

        if ($data instanceof JsonResource) {
            return $this->formatResourceResponse(...func_get_args());
        }

        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        }

        return $this->formatArrayResponse(Arr::wrap($data), $message, $code, $headers, $option);
    }

    /**
     * Return an fail response.
     *
     * @param string $message
     * @param int    $code
     * @param mixed  $errors
     * @param array  $header
     * @param int    $options
     *
     * @return JsonResponse
     */
    public function fail(
        string $message = '',
        int $code = self::HTTP_BAD_REQUEST,
        $errors = null,
        array $header = [],
        int $options = 0
    ) {
        return $this->error($message, $code, $errors, $header, $options);
    }

    /**
     * Return an error response.
     *
     * @param string $message
     * @param int    $code
     * @param mixed  $errors
     * @param array  $header
     * @param int    $options
     *
     * @return JsonResponse
     */
    public function error(
        string $message = '',
        int $code = self::HTTP_INTERNAL_SERVER_ERROR,
        $errors = null,
        array $header = [],
        int $options = 0
    ) {
        $response = $this->respond(
            $this->formatData(null, $message, $code, $errors),
            $code,
            $header,
            $options
        );
        if (is_null($errors)) {
            $response->throwResponse();
        }

        return $response;
    }

    /**
     * Respond with a created response and associate a location if provided.
     *
     * @param null   $data
     * @param string $message
     * @param string $location
     *
     * @return JsonResponse
     */
    public function created($data = null, string $message = '', string $location = '')
    {
        $response = $this->success($data, $message, self::HTTP_CREATED);
        if ($location) {
            $response->header('Location', $location);
        }

        return $response;
    }

    /**
     * Respond with an accepted response and associate a location and/or content if provided.
     *
     * @param null   $data
     * @param string $message
     * @param string $location
     *
     * @return JsonResponse
     */
    public function accepted($data = null, string $message = '', string $location = '')
    {
        $response = $this->success($data, $message, self::HTTP_ACCEPTED);
        if ($location) {
            $response->header('Location', $location);
        }

        return $response;
    }

    /**
     * Respond with a no content response.
     *
     * @param string $message
     *
     * @return JsonResponse
     */
    public function noContent(string $message = '')
    {
        return $this->success(null, $message, self::HTTP_NO_CONTENT);
    }

    /**
     * Return a 401 unauthorized error.
     *
     * @param string $message
     *
     * @return JsonResponse
     */
    public function errorUnauthorized(string $message = '')
    {
        return $this->error($message, self::HTTP_UNAUTHORIZED);
    }

    /**
     * Return a 403 forbidden error.
     *
     * @param string $message
     *
     * @return JsonResponse
     */
    public function errorForbidden(string $message = '')
    {
        return $this->error($message, self::HTTP_FORBIDDEN);
    }

    /**
     * Return a 404 not found error.
     *
     * @param string $message
     *
     * @return JsonResponse
     */
    public function errorNotFound(string $message = '')
    {
        return $this->error($message, self::HTTP_NOT_FOUND);
    }

    /**
     * Return a 405 method not allowed error.
     *
     * @param string $message
     *
     * @return JsonResponse
     */
    public function errorMethodNotAllowed(string $message = '')
    {
        return $this->fail($message, self::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * Return a 422 unprocessable entity error.
     *
     * @param string $message
     *
     * @return JsonResponse
     */
    public function errorUnprocessableEntity(string $message = '')
    {
        return $this->fail($message, self::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Format return data structure.
     *
     * @param mixed  $data
     * @param string $message
     * @param int    $code
     * @param mixed  $errors
     *
     * @return array
     */
    protected function formatData($data, string $message, int $code, $errors = null)
    {
        switch ($code) {
            case $code >= 400 && $code <= 499:
                $status = 'fail';
                break;
            case $code >= 500 && $code <= 599:
                $status = 'error';
                break;
            default:
                $status = 'success';
                break;
        }

        $message || $message = \config("response.code.{$status}");
        if (false !== strpos($message, '|')) {
            list ($message, $businessCode) = explode('|', $message, 2);
        }

        return [
            'status' => $status,
            'code' => (string)($businessCode ?? $code),
            'message' => $message ?: '',
            'data' => $data ?: (object)$data,
            'errors' => $errors ?: (object)[],
        ];
    }

    /**
     * Format ResourceCollection data.
     *
     * @param ResourceCollection $resource
     * @param string             $message
     * @param int                $code
     * @param array              $headers
     * @param int                $option
     *
     * @return JsonResponse
     */
    protected function formatResourceCollectionResponse(
        ResourceCollection $resource,
        string $message = '',
        $code = 200,
        array $headers = [],
        int $option = 0
    ) {
        $dataField = \config('response.format.collection_field', 'data');
        $data = \array_merge_recursive(
            [$dataField => $resource->resolve(\request())],
            $resource->with(\request()),
            $resource->additional
        );
        if ($resource->resource instanceof AbstractPaginator) {
            $paginated = $resource->resource->toArray();
            $paginationInformation = $this->formatPaginatedData($paginated);
            $data = \array_merge_recursive($data, $paginationInformation);
        }

        return \tap(
            $this->respond($this->formatData($data, $message, $code), $code, $headers, $option),
            function ($response) use ($resource) {
                $response->original = $resource->resource->map(function ($item) {
                    return $item->resource;
                });
                $resource->withResponse(\request(), $response);
            }
        );
    }

    /**
     * Format paginated response.
     *
     * @param        $resource
     * @param string $message
     * @param int    $code
     * @param array  $headers
     * @param int    $option
     *
     * @return JsonResponse
     */
    protected function formatPaginatedResponse($resource, string $message = '', int $code = 200, array $headers = [], int $option = 0)
    {
        $paginated = $resource->toArray();
        $paginationInformation = $this->formatPaginatedData($paginated);
        $dataField = \config('response.format.collection_field', 'data');
        $data = \array_merge_recursive([$dataField => $paginated['data']], $paginationInformation);

        return $this->respond($this->formatData($data, $message, $code), $code, $headers, $option);
    }

    /**
     * Format paginated data.
     *
     * @param array $paginated
     *
     * @return array
     */
    protected function formatPaginatedData(array $paginated): array
    {
        $metaField = \config('response.format.pagination.meta_field', 'meta');
        $metaReturnFields = \config('response.format.pagination.return_fields', [
            'total',
            'per_page',
            'current_page',
            'last_page',
            'from',
            'to',
            'path',
            'prev_page_url',
            'next_page_url',
        ]);

        return [$metaField => Arr::only($paginated, $metaReturnFields)];
    }

    /**
     * Format JsonResource Data.
     *
     * @param JsonResource $resource
     * @param string       $message
     * @param int          $code
     * @param array        $headers
     * @param int          $option
     *
     * @return JsonResponse
     */
    protected function formatResourceResponse(
        JsonResource $resource,
        string $message = '',
        int $code = 200,
        array $headers = [],
        int $option = 0
    ) {
        $resourceData = \array_merge_recursive($resource->resolve(\request()), $resource->with(\request()), $resource->additional);
        return \tap(
            $this->respond($this->formatData($resourceData, $message, $code), $code, $headers, $option),
            function ($response) use ($resource) {
                $response->original = $resource->resource;
                $resource->withResponse(\request(), $response);
            }
        );
    }

    /**
     * Format normal array data.
     *
     * @param array  $data
     * @param string $message
     * @param int    $code
     * @param array  $headers
     * @param int    $option
     *
     * @return JsonResponse
     */
    protected function formatArrayResponse(
        array $data,
        string $message = '',
        int $code = 200,
        array $headers = [],
        int $option = 0
    ) {
        return $this->respond($this->formatData($data, $message, $code), $code, $headers, $option);
    }

    /**
     * Return a new JSON response from the application.
     *
     * @param array $data
     * @param int   $status
     * @param array $headers
     * @param int   $options
     *
     * @return JsonResponse
     */
    protected function respond(array $data, int $status = 200, array $headers = [], int $options = 0)
    {
        if (false === \config('response.is_restful')) {
            $status = self::HTTP_OK;
        }

        return \response()->json($data, $status, $headers, $options);
    }
}