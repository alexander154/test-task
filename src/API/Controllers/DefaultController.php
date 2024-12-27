<?php

namespace TestTaskMailing\API\Controllers;

use TestTaskMailing\API\AbstractController;
use TestTaskMailing\API\Models\ServerResponse;

class DefaultController extends AbstractController
{
    public function process(): ServerResponse
    {
        return $this->makeErrorResponse('Конечная точка API не существует', 404);
    }
}