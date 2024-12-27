<?php

namespace TestTaskMailing\Mailing\SubscribersImporter\Importers;

use TestTaskMailing\Mailing\SubscribersImporter\AbstractSubscribersImporter;
use TestTaskMailing\Mailing\SubscribersImporter\Exception\InvalidPendingSubscriberException;

class FromJSONSubscribersImporter extends AbstractSubscribersImporter
{
    private array $subscribers_array;

    public function setJSON(string $json_raw): bool
    {
        $json_assoc = json_decode($json_raw, true);

        if(is_null($json_assoc) or !isset($json_assoc['subscribers']) or !is_array($json_assoc['subscribers'])){
            return false;
        }

        $this->subscribers_array = $json_assoc['subscribers'];

        return true;
    }

    public function import(): int
    {
        $mailing_manager = $this->mailing_manager;

        $subscribers_array = $this->subscribers_array;

        $imported_cnt = 0;

        $subscribers = [];

        foreach ($subscribers_array as $pending_subscriber_value) {
            if (!isset($pending_subscriber_value['number'], $pending_subscriber_value['name'])
                or !(is_string($pending_subscriber_value['number']) and is_string($pending_subscriber_value['name']))
            ) {
                continue;
            }

            try {
                $subscriber = $this->getSubscriberFromExternalValues($pending_subscriber_value['number'], $pending_subscriber_value['name']);
                $imported_cnt++;
            } catch (InvalidPendingSubscriberException $e) {
                continue;
            }

            $subscribers[] = $subscriber;
        }

        $mailing_manager->saveSubscribers($subscribers);

        return $imported_cnt;
    }
}