<?php

namespace TestTaskMailing\Mailing\Storage\Exception;

use Exception;

class StorageException extends Exception
{
    const ERR_IN_PROGRESS = 1000001;
}