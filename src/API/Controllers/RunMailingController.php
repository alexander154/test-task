<?php

namespace TestTaskMailing\API\Controllers;

use TestTaskMailing\API\AbstractController;
use TestTaskMailing\API\Exception\APIException;
use TestTaskMailing\API\Models\ServerResponse;
use TestTaskMailing\Logger\Logger;
use TestTaskMailing\Mailing\Exception\MailingManagerException;
use TestTaskMailing\Mailing\MailingManager;
use TestTaskMailing\Mailing\Sender\QueueSender;
use TestTaskMailing\Mailing\Storage\Exception\StorageException;
use TestTaskMailing\Mailing\Storage\MySQLStorage;

class RunMailingController extends AbstractController
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

            $storage->setDirectoryForFailedBatches(__DIR__ . "/../../../tmp_mailing_data");
            $storage->setSentMsgQueryBatchSize(100);
        } catch (StorageException $e) {
            Logger::write($e, Logger::LEVEL_ERROR);
            return $this->makeErrorResponse("Ошибка при создании подключения к БД, попробуйте повторить запрос снова.", 503);
        }

        if (!isset($input_json['id'])) {
            return $this->makeErrorResponse("Не передан id рассылки.", 400);
        }

        $id = $input_json['id'];

        if (!is_int($id) or $id < 1) {
            return $this->makeErrorResponse("Значение id рассылки передано некорректно.", 400);
        }

        try {
            $mailing_manager = new MailingManager($storage);
            $mailing = $mailing_manager->loadMailing($id);
            if ($mailing === false) {
                return $this->makeErrorResponse("Рассылка с id " . $id . " не найдена в БД", 404);
            }

            $sender = new QueueSender();

            $mailing_manager->setSender($sender);

            if (isset($input_json['resend']) and is_bool($input_json['resend'])) {
                $resend = $input_json['resend'];
            } else {
                $resend = false;
            }

            $cnt_sent = $mailing_manager->runMailing($mailing, $resend);

            return $this->makeResponse(["id" => $id, "cntSent" => $cnt_sent]);
        } catch (MailingManagerException $e) {
            Logger::write($e);
            return $this->makeErrorResponse("Ошибка при загрузке рассылки из БД, попробуйте повторить запрос: " . $e->getMessage(), 503);
        }
    }
}