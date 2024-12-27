<?php

namespace TestTaskMailing\Mailing\Sender;

use TestTaskMailing\Mailing\Models\Mailing;
use TestTaskMailing\Mailing\Models\Subscriber;
use TestTaskMailing\Mailing\Sender\Contract\SenderInterface;

class QueueSender implements SenderInterface
{
    public function send(Mailing $mailing, Subscriber $subscriber): void
    {
        /*
         * @TODO: реализовать логику отправки сообщения в очередь, в рамках тестового задания не требуется.
         */
    }
}