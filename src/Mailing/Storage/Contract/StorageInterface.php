<?php

namespace TestTaskMailing\Mailing\Storage\Contract;

use Closure;
use TestTaskMailing\Mailing\Models\Mailing;
use TestTaskMailing\Mailing\Models\Subscriber;
use TestTaskMailing\Mailing\Storage\Exception\ChangeMailingStateException;
use TestTaskMailing\Mailing\Storage\Exception\StorageException;

interface StorageInterface
{
    const MAILING_STATE_FREE = 0;
    const MAILING_STATE_BUSY = 1;
    const ERR_MAILING_IN_PROGRESS = 10000;
    /**
     * @throws StorageException
     */
    public function __construct();

    /**
     * @throws StorageException
     */
    public function saveSubscriber(Subscriber $subscriber): void;

    /**
     * @param Subscriber[] $subscribers
     * @throws StorageException
     */
    public function saveSubscribers(array $subscribers): void;

    /**
     * @throws StorageException
     */
    public function saveMailing(string $title, string $text): Mailing;
    /**
     * @throws ChangeMailingStateException
     */
    public function changeMailingState(Mailing $mailing, int $state): void;

    /**
     * @throws StorageException
     */
    public function loadMailing(int $id);
    /**
     * @throws StorageException
     */
    public function sendMessagesAndSaveInfo(Mailing $mailing, Closure $sending_closure, bool $resend=false): int;
}