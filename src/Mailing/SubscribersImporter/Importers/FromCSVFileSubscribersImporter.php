<?php

namespace TestTaskMailing\Mailing\SubscribersImporter\Importers;

use TestTaskMailing\Mailing\SubscribersImporter\AbstractSubscribersImporter;
use TestTaskMailing\Mailing\SubscribersImporter\Exception\InvalidPendingSubscriberException;

class FromCSVFileSubscribersImporter extends AbstractSubscribersImporter
{
    private string $file_path;
    public function setFile(string $file_path): bool
    {
        if(!file_exists($file_path)){
            return false;
        }

        $this->file_path = $file_path;

        return true;
    }

    public function import(): int
    {
        $mailing_manager = $this->mailing_manager;

        $csv_contents = file($this->file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $subscribers = [];

        $imported_cnt = 0;

        foreach ($csv_contents as $item) {
            $subscriber_array = str_getcsv($item);
            if (!isset($subscriber_array[0], $subscriber_array[1])) {
                continue;
            }

            try {
                $subscriber = $this->getSubscriberFromExternalValues($subscriber_array[0], $subscriber_array[1]);
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