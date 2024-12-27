<?php

namespace TestTaskMailing\API\Controllers;

use TestTaskMailing\API\AbstractController;
use TestTaskMailing\API\Models\ServerResponse;
use TestTaskMailing\Logger\Logger;
use TestTaskMailing\Mailing\Exception\AddSubscriberException;
use TestTaskMailing\Mailing\MailingManager;
use TestTaskMailing\Mailing\Storage\Exception\StorageException;
use TestTaskMailing\Mailing\Storage\MySQLStorage;
use TestTaskMailing\Mailing\SubscribersImporter\Importers\FromCSVFileSubscribersImporter;

class AddSubscribersFromFileController extends AbstractController
{
    public function process(): ServerResponse
    {
        if (count($this->API->getUploadedFiles()) !== 1 or !isset($this->API->getUploadedFiles()[array_key_first($this->API->getUploadedFiles())]['tmp_name'])) {
            return $this->makeErrorResponse("Файл с подписчиками не был загружен, повторите попытку", 400);
        }

        try {
            $storage = new MySQLStorage();
        } catch (StorageException $e) {
            Logger::write($e, Logger::LEVEL_ERROR);
            return $this->makeErrorResponse("Ошибка при создании подключения к БД, попробуйте повторить запрос снова.", 503);
        }

        $csv_importer = new FromCSVFileSubscribersImporter(new MailingManager($storage));
        $csv_importer->setFile($this->API->getUploadedFiles()[array_key_first($this->API->getUploadedFiles())]['tmp_name']);
        try {
            $imported_cnt = $csv_importer->import();

            return $this->makeResponse("Файл обработан, добавлено " . $imported_cnt . " подписчиков.");
        } catch (AddSubscriberException $e) {
            $http_code = 500;

            if ($e->getCode() === 409) {
                $http_code = $e->getCode();
            }

            return $this->makeErrorResponse($e->getMessage(), $http_code);
        }
    }
}