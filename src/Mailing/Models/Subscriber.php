<?php

namespace TestTaskMailing\Mailing\Models;

class Subscriber
{
    private string $number;
    private string $name;

    public function __construct(string $number, string $name)
    {
        $this->name = $name;
        $this->number = $number;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }
}