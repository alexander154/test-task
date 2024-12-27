<?php

namespace TestTaskMailing\API\Controllers;

use TestTaskMailing\API\AbstractController;
use TestTaskMailing\API\Exception\APIException;
use TestTaskMailing\API\Models\ServerResponse;
use TestTaskMailing\Logger\Logger;
use TestTaskMailing\Mailing\Exception\CreateMailingException;
use TestTaskMailing\Mailing\MailingManager;
use TestTaskMailing\Mailing\Storage\Exception\StorageException;
use TestTaskMailing\Mailing\Storage\MySQLStorage;

class CreateMailingController extends AbstractController
{
    public function process(): ServerResponse
    {
        try {
            $input_json = $this->API->getRawInput(true);
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

        $mailing_manager = new MailingManager($storage);

        if (!isset($input_json['title'], $input_json['text'])) {
            return $this->makeErrorResponse("Не переданы заголовок и текст рассылки.", 400);
        }

        $title = (string)$input_json['title'];
        $text = (string)$input_json['text'];

        try {
            $mailing = $mailing_manager->createMailing($title, $text);
            $id = $mailing->getId();
        } catch (CreateMailingException $e) {
            Logger::write($e);
            return $this->makeErrorResponse("Ошибка при создании рассылки, попробуйте повторить запрос: " . $e->getMessage(), 503);
        }

        return $this->makeResponse(["id" => $id], 201);
    }
}