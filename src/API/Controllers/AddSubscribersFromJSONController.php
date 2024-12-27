<?php

namespace TestTaskMailing\API\Controllers;

use TestTaskMailing\API\AbstractController;
use TestTaskMailing\API\Exception\APIException;
use TestTaskMailing\API\Models\ServerResponse;
use TestTaskMailing\Logger\Logger;
use TestTaskMailing\Mailing\Exception\AddSubscriberException;
use TestTaskMailing\Mailing\MailingManager;
use TestTaskMailing\Mailing\Storage\Exception\StorageException;
use TestTaskMailing\Mailing\Storage\MySQLStorage;
use TestTaskMailing\Mailing\SubscribersImporter\Importers\FromJSONSubscribersImporter;

class AddSubscribersFromJSONController extends AbstractController
{
    public function process(): ServerResponse
    {
        try {
            $input_json = $this->API->getRawInput();
        } catch (APIException $e) {
            Logger::write($e);
            return $this->makeErrorResponse("Ожидался JSON, но он передан некорректно.", 415);
        }

        try {
            $storage = new MySQLStorage();
        } catch (StorageException $e) {
            Logger::write($e, Logger::LEVEL_ERROR);
            return $this->makeErrorResponse("Ошибка при создании подключения к БД, попробуйте повторить запрос снова.", 503);
        }

        $json_importer = new FromJSONSubscribersImporter(new MailingManager($storage));

        if(!$json_importer->setJSON($input_json))
        {
            return $this->makeErrorResponse("Не передан массив с подписчиками.", 400);
        }

        try {
            $imported_cnt = $json_importer->import();

            return $this->makeResponse("Данные обработаны, добавлено " . $imported_cnt . " подписчиков.");
        } catch (AddSubscriberException $e) {
            $http_code = 500;

            if ($e->getCode() === 409) {
                $http_code = $e->getCode();
            }

            return $this->makeErrorResponse($e->getMessage(), $http_code);
        }
    }
}