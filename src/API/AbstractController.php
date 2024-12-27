<?php

namespace TestTaskMailing\API;

use TestTaskMailing\API\Models\ServerResponse;

abstract class AbstractController
{
    protected API $API;

    public function __construct(API $API)
    {
        $this->API = $API;
    }

    protected function makeErrorResponse($data, int $http_code = 500): ServerResponse
    {
        return new ServerResponse($data, $http_code);
    }

    protected function makeResponse($data, int $http_code = 200): ServerResponse
    {
        return new ServerResponse($data, $http_code);
    }

    abstract public function process(): ServerResponse;
}