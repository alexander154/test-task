<?php

namespace TestTaskMailing\API\Models;

class ServerResponse
{
    private int $http_code;
    private $data;

    public function __construct($data, int $http_code)
    {
        $this->data = $data;
        $this->http_code = $http_code;
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * @return int
     */
    public function getHttpCode(): int
    {
        return $this->http_code;
    }
}