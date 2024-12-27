<?php

namespace TestTaskMailing\Mailing\Storage;

use Closure;
use PDO;
use PDOException;
use TestTaskMailing\Logger\Logger;
use TestTaskMailing\Mailing\Models\Mailing;
use TestTaskMailing\Mailing\Models\Subscriber;
use TestTaskMailing\Mailing\Storage\Contract\StorageInterface;
use TestTaskMailing\Mailing\Storage\Exception\ChangeMailingStateException;
use TestTaskMailing\Mailing\Storage\Exception\StorageException;

class MySQLStorage implements StorageInterface
{
    private int $sent_msg_query_batch_size = 100;
    private string $dir_for_failed_batches;
    private PDO $PDO;

    /**
     * @throws StorageException
     */
    public function __construct()
    {
        $dsn = 'mysql:host=mysql;dbname=mydb;charset=utf8mb4';
        $username = 'root';
        $password = '';

        try {
            $PDO = new PDO($dsn, $username, $password);
            $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $this->PDO = $PDO;
        } catch (PDOException $e) {
            throw new StorageException("Can't init storage (MySQL): " . $e->getMessage());
        }
    }

    public function setSentMsgQueryBatchSize(int $size)
    {
        $this->sent_msg_query_batch_size = $size;
    }

    /**
     * @throws StorageException
     */
    public function setDirectoryForFailedBatches(string $directory)
    {
        if (!is_dir($directory) and !mkdir($directory)) {
            throw new StorageException("Передан некорректный каталог для сохранения временных файлов.");
        }

        $this->dir_for_failed_batches = $directory;
    }

    /**
     * @throws StorageException
     */
    public function saveSubscriber(Subscriber $subscriber): void
    {
        $PDO = $this->PDO;

        $query = "INSERT INTO `subscribers` (`number`, `name`) VALUES (:number, :name);";
        $stmt = $PDO->prepare($query);

        $stmt->bindValue(':number', $subscriber->getNumber());
        $stmt->bindValue(':name', $subscriber->getName());

        try {
            $stmt->execute();
        } catch (PDOException $e) {
            throw new StorageException("Ошибка MySQL: " . $e->getMessage());
        }
    }

    public function saveMailing(string $title, string $text): Mailing
    {
        $PDO = $this->PDO;

        $query = "INSERT INTO `mailings_list` (`id`, `added`, `title`, `text`) 
VALUES ('0', NOW(), :title, :text)";
        $stmt = $PDO->prepare($query);

        $stmt->bindValue(':title', $title);
        $stmt->bindValue(':text', $text);

        try {
            $stmt->execute();

            return new Mailing($PDO->lastInsertId(), $title, $text);

        } catch (PDOException $e) {
            Logger::write($e);
            throw new StorageException("Ошибка MySQL: " . $e->getMessage());
        }
    }

    public function loadMailing(int $id)
    {
        $PDO = $this->PDO;

        $query = "SELECT `id`, `title`, `text` FROM `mailings_list` WHERE `id`=:id LIMIT 1;";
        $stmt = $PDO->prepare($query);

        $stmt->bindValue(':id', $id);

        try {
            $stmt->execute();

            if ($stmt->rowCount() !== 1) {
                return false;
            }

            $row = $stmt->fetch(PDO::FETCH_OBJ);

            return new Mailing($row->id, $row->title, $row->text);
        } catch (PDOException $e) {
            throw new StorageException("Ошибка MySQL: " . $e->getMessage());
        }
    }

    /**
     * @param Mailing $mailing
     * @return Subscriber[]
     * @throws StorageException
     */
    private function loadSubscribersForMailing(Mailing $mailing, bool $unused_only=true): array
    {
        $PDO = $this->PDO;

        $subscribers = [];

        if($unused_only) {
            $query = "SELECT `subscribers`.`number` AS `number`, `subscribers`.`name` AS `name` FROM `subscribers` LEFT JOIN `messages` USE INDEX(`mailing_id_and_subscriber_number`) 
         ON `messages`.`mailing_id`=:id AND `messages`.`subscriber_number`=`subscribers`.`number` WHERE `messages`.`subscriber_number` IS NULL;";

            $stmt = $PDO->prepare($query);

            $stmt->bindValue(':id', $mailing->getId());
        }else{
            $query = "SELECT `subscribers`.`number` AS `number`, `subscribers`.`name` AS `name` FROM `subscribers`;";
            $stmt = $PDO->prepare($query);
        }

        try {
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $subscribers[] = new Subscriber($row['number'], $row['name']);
                }
            }

            return $subscribers;
        } catch (PDOException $e) {
            throw new StorageException("Ошибка MySQL: " . $e->getMessage());
        }
    }

    public function saveSubscribers(array $subscribers): void
    {
        $PDO = $this->PDO;

        try {
            $PDO->beginTransaction();
        } catch (PDOException $e) {
            throw new StorageException("Ошибка БД при старте транзакции: " . $e->getMessage());
        }

        foreach ($subscribers as $subscriber) {
            try {
                $this->saveSubscriber($subscriber);
            } catch (StorageException $e) {
                $PDO->rollBack();

                $error_code = 0;

                $message = "Не получилось сохранить подписчика " . $subscriber->getName() .
                    "[" . $subscriber->getNumber() . "]";

                if (stripos($e->getMessage(), "1062 Duplicate entry") !== false) {
                    $message .= " (подписчик с таким номером уже существует в БД)";
                    $error_code = 409;
                } else {
                    Logger::write($e, Logger::LEVEL_ERROR);
                }

                throw new StorageException($message, $error_code);
            }
        }

        $PDO->commit();
    }

    public function sendMessagesAndSaveInfo(Mailing $mailing, Closure $sending_closure, bool $resend = false): int
    {
        $PDO = $this->PDO;

        if (!isset($this->dir_for_failed_batches)) {
            throw new StorageException("Не установлен каталог для временных файлов. Работа невозможна.");
        }

        $PDO->beginTransaction();
        try {

            $query = "SELECT `state` FROM `mailings_list` WHERE `id`=:id LIMIT 1 FOR UPDATE;";

            $stmt = $PDO->prepare($query);

            $stmt->bindValue(':id', $mailing->getId(), PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() !== 1) {
                Logger::write($stmt->rowCount() . ';' . $mailing->getId());
                $PDO->rollBack();
                throw new StorageException("Рассылки с ID " . $mailing->getId() . "не существует");
            }

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ((int)$row['state'] !== self::MAILING_STATE_FREE) {
                $PDO->rollBack();
                throw new StorageException("Рассылка в процессе, повторите запрос позднее.", self::ERR_MAILING_IN_PROGRESS);
            }

            $this->changeMailingState($mailing, self::MAILING_STATE_BUSY);

            $PDO->commit();
        } catch (PDOException|ChangeMailingStateException $e) {
            Logger::write("Не получилось информацию о рассылке или её запустить.");
            Logger::write($e);

            if ($PDO->inTransaction()) {
                $PDO->rollBack();
            }

            throw new StorageException("Не получилось информацию о рассылке или её запустить.");
        }

        try {
            $this->saveFailedSQLBatches($mailing);
        } catch (StorageException $e) {
            try {
                $this->changeMailingState($mailing, self::MAILING_STATE_FREE);
            } catch (ChangeMailingStateException $e) {
                Logger::write("Не удалось установить статус " . self::MAILING_STATE_FREE . " для рассылки" . $mailing->getId());
            }
        }

        $subscribers = $this->loadSubscribersForMailing($mailing, !$resend);

        $cnt_subscribers = count($subscribers);

        $cnt_sent = 0;

        if ($cnt_subscribers > 0) {

            $values = [];
            $has_fails = false;

            $tmp_sql_filename = $this->dir_for_failed_batches . "/" . $mailing->getId() . ".json";

            $fp = fopen($tmp_sql_filename, "w");

            foreach ($subscribers as $subscriber) {
                if ($sending_closure($mailing, $subscriber) === true) {

                    $values[] = ["mailing_id" => $mailing->getId(), "subscriber_number" => $subscriber->getNumber()];

                    $cnt_sent++;

                    if (count($values) === $this->sent_msg_query_batch_size) {
                        $this->saveBatch($values, $has_fails, $fp);
                    }
                }
            }

            if (count($values) > 0) {
                $this->saveBatch($values, $has_fails, $fp);
            }

            fclose($fp);

            if (!$has_fails) {
                unlink($tmp_sql_filename);
            }
        }

        try {
            $this->changeMailingState($mailing, self::MAILING_STATE_FREE);
        } catch (ChangeMailingStateException $e) {
            Logger::write("Ошибка при обновлении статуса готово рассылки" . $e->getMessage());
        }

        return $cnt_sent;
    }

    public function changeMailingState(Mailing $mailing, int $state): void
    {
        $PDO = $this->PDO;

        $query = "UPDATE `mailings_list` SET `state`=:state WHERE `id`=:id LIMIT 1;";

        $stmt = $PDO->prepare($query);

        $stmt->bindValue(':state', $state, PDO::PARAM_INT);
        $stmt->bindValue(':id', $mailing->getId(), PDO::PARAM_INT);

        $stmt->execute();
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            throw new ChangeMailingStateException("Не удалось обновить статус рассылки в БД : " . $e->getMessage());
        }

    }

    private function generateBatchSQLQuery(array $messages_values): string
    {
        $PDO = $this->PDO;
        $sql_prefix = "INSERT INTO `messages` (`id`, `mailing_id`, `subscriber_number`, `sent_at`) VALUES ";

        $prepared_values = [];

        foreach ($messages_values as $message_value) {
            $prepared_values[] = "('0', " . $PDO->quote($message_value['mailing_id']) . ", " . $PDO->quote($message_value['subscriber_number']) . ", NOW())";
        }

        $values_str = implode(", ", $prepared_values);
        return $sql_prefix . $values_str . ";";
    }

    private function saveBatch(array &$values, bool &$has_fails, $fp): void
    {
        $PDO = $this->PDO;

        $sql_batch = $this->generateBatchSQLQuery($values);

        $stmt_send = $PDO->prepare($sql_batch);
        try {
            $stmt_send->execute();
        } catch (PDOException $e) {
            fwrite($fp, json_encode($values) . PHP_EOL);
            $has_fails = true;
        }

        $values = [];
    }

    /**
     * @throws StorageException
     */
    private function saveFailedSQLBatches(Mailing $mailing)
    {
        $PDO = $this->PDO;

        $tmp_sql_filename = $this->dir_for_failed_batches . "/" . $mailing->getId() . ".json";
        if (file_exists($tmp_sql_filename)) {
            $PDO->beginTransaction();

            $file = file($tmp_sql_filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($file as $raw_json) {
                $messages_values = json_decode($raw_json, true);
                $sql_batch = $this->generateBatchSQLQuery($messages_values);

                $PDO->exec($sql_batch);
            }

            try {
                $PDO->commit();

                unlink($tmp_sql_filename);
            } catch (PDOException $e) {
                throw new StorageException("Обнаружены несохраненные данные для вставки в БД, но вставить их не удалось.");
            }
        }
    }
}