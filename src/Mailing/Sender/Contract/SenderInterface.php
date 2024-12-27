<?php

namespace TestTaskMailing\Mailing\Sender\Contract;

use TestTaskMailing\Mailing\Models\Mailing;
use TestTaskMailing\Mailing\Models\Subscriber;
use TestTaskMailing\Mailing\Sender\Exception\SendMessageException;

interface SenderInterface
{
    /**
     * @param Mailing $mailing
     * @param Subscriber $subscriber
     * @return void
     * @throws SendMessageException
     */
    public function send(Mailing $mailing, Subscriber $subscriber): void;
}