<?php

namespace TestTaskMailing\API;

use TestTaskMailing\API\Exception\APIException;

class API
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';
    const METHOD_UPDATE = 'UPDATE';
    protected array $routes_list;

    public function __construct()
    {
        $this->routes_list[0]["controller"] = "Default";
        $this->routes_list[0]["method"] = "Both";
        $this->routes_list[0]["route"] = "default";
    }

    public function addRoute(string $route, string $controller, ?string $method = null): void
    {
        $this->routes_list[] = ["controller" => $controller, "method" => $method, "route" => $route];
    }

    /**
     * @throws APIException
     */
    public function getRawInput(bool $as_json = false)
    {
        $input = file_get_contents('php://input');
        if ($as_json) {
            $input = json_decode($input, true);
            if (is_null($input)) {
                throw new APIException("Передан некорректный JSON");
            }
        }

        return $input;
    }

    public function makeResponse($data, $status_code = 200): void
    {
        http_response_code($status_code);
        Header("Content-Type: application/json; charset=utf-8");

        echo json_encode($data, JSON_UNESCAPED_UNICODE);

        exit();
    }

    public function run(): void
    {

        if (preg_match('/^[^?]*/', $_SERVER['REQUEST_URI'], $matches)) {
            $uri = $matches[0];
        }

        if (!isset($uri)) {
            $current_controller_prefix = $this->routes_list['default']['controller'];
        } else {
            foreach ($this->routes_list as $controller_settings) {
                if ($controller_settings['route'] === $uri) {
                    $controller_method = $controller_settings['method'];

                    if (!is_null($controller_method)) {
                        if ($this->getRequestMethod() !== $controller_method) {
                            continue;
                        }
                    }

                    $current_controller_prefix = $controller_settings['controller'];
                    break;
                }
            }

            if (!isset($current_controller_prefix)) {
                $current_controller_prefix = $this->routes_list[0]['controller'];
            }
        }

        $controller_file_name = __DIR__ . "/Controllers/" . $current_controller_prefix . "Controller.php";
        $class_file_name = "\\TestTaskMailing\API\Controllers\\" . $current_controller_prefix . "Controller";

        if (file_exists($controller_file_name)) {
            require_once($controller_file_name);
            $controller = new $class_file_name($this);
            if ($controller instanceof AbstractController) {
                $response = $controller->process();

                $this->makeResponse($response->getData(), $response->getHttpCode());
            }
        } else {
            $this->makeResponse("Невозможно загрузить контроллер", 500);
        }
    }

    public function getRequestMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function getUploadedFiles(): array
    {
        return $_FILES;
    }
}