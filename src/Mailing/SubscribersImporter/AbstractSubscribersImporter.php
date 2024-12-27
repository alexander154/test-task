<?php

namespace TestTaskMailing\Mailing\SubscribersImporter;

use TestTaskMailing\Mailing\Exception\AddSubscriberException;
use TestTaskMailing\Mailing\MailingManager;
use TestTaskMailing\Mailing\Models\Subscriber;
use TestTaskMailing\Mailing\SubscribersImporter\Exception\InvalidPendingSubscriberException;

abstract class AbstractSubscribersImporter
{
    protected MailingManager $mailing_manager;
    public function __construct(MailingManager $mailing_manager)
    {
        $this->mailing_manager = $mailing_manager;
    }

    /**
     * @throws InvalidPendingSubscriberException
     */
    protected function getSubscriberFromExternalValues(string $number, string $name): Subscriber
    {
        $number = trim($number);
        $name = trim($name);

        if (mb_strlen($number) < 1 or mb_strlen($name) < 1) {
           throw new InvalidPendingSubscriberException();
        }

        return new Subscriber($number, $name);
    }
    /**
     * @throws AddSubscriberException
     */
    abstract public function import(): int;
}