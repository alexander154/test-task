<?php

namespace TestTaskMailing\Mailing;

use TestTaskMailing\Logger\Logger;
use TestTaskMailing\Mailing\Exception\AddSubscriberException;
use TestTaskMailing\Mailing\Exception\CreateMailingException;
use TestTaskMailing\Mailing\Exception\MailingManagerException;
use TestTaskMailing\Mailing\Models\Mailing;
use TestTaskMailing\Mailing\Models\Subscriber;
use TestTaskMailing\Mailing\Sender\Contract\SenderInterface;
use TestTaskMailing\Mailing\Sender\Exception\SendMessageException;
use TestTaskMailing\Mailing\Storage\Contract\StorageInterface;
use TestTaskMailing\Mailing\Storage\Exception\StorageException;

class MailingManager
{
    private StorageInterface $storage;
    private SenderInterface $sender;

    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @param SenderInterface $sender
     */
    public function setSender(SenderInterface $sender): void
    {
        $this->sender = $sender;
    }

    /**
     * @throws AddSubscriberException
     */
    public function saveSubscribers(array $subscribers): void
    {
        try {
            $this->storage->saveSubscribers($subscribers);
        } catch (StorageException $e) {
            if ($e->getCode() != 409) {
                Logger::write($e, Logger::LEVEL_ERROR);
            }

            throw new AddSubscriberException("Ошибка добавления пользователей в БД, подробнее: " . $e->getMessage(), $e->getCode());
        }
    }

    /**
     * @throws CreateMailingException
     */
    public function createMailing(string $title, string $text): Mailing
    {
        $storage = $this->storage;

        try {
            return $storage->saveMailing($title, $text);
        } catch (Storage\Exception\StorageException $e) {
            Logger::write($e, Logger::LEVEL_ERROR);
            throw new CreateMailingException("Ошибка сохранения новой рассылки в БД.");
        }
    }

    /**
     * @throws MailingManagerException
     */
    public function runMailing(Mailing $mailing, bool $resend=false): int
    {
        if (!isset($this->sender)) {
            throw new MailingManagerException("Вы не выбрали способ отправки (метод setSender())");
        }

        $storage = $this->storage;
        $sender = $this->sender;

        $sending_closure = function (Mailing $mailing, Subscriber $subscriber) use ($sender): bool {
            try {
                $sender->send($mailing, $subscriber);
                return true;
            } catch (SendMessageException $e) {
                return false;
            }
        };

        try {
            return $storage->sendMessagesAndSaveInfo($mailing, $sending_closure, $resend);
        } catch (StorageException $e) {
            Logger::write("Ошибка при отправке сообщений в очередь: " . $e->getMessage(), Logger::LEVEL_ERROR);
            if ($e->getCode() === StorageInterface::ERR_MAILING_IN_PROGRESS) {
                throw new MailingManagerException("Рассылка уже выполняется, попробуйте повторить позднее.");
            } else {
                throw new MailingManagerException("Ошибка при отправке сообщений в очередь.");
            }
        }
    }

    /**
     * @throws MailingManagerException
     */
    public function loadMailing(int $id)
    {
        try {
            return $this->storage->loadMailing($id);
        } catch (StorageException $e) {
            Logger::write($e, Logger::LEVEL_ERROR);
            throw new MailingManagerException("Ошибка при загрузки рассылки из БД.");
        }
    }
}