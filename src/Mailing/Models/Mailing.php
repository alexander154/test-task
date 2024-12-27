<?php

namespace TestTaskMailing\Mailing\Models;

class Mailing
{
    private string $title;
    private string $text;
    private int $id;

    public function __construct(int $id, string $title, string $text)
    {
        $this->id = $id;
        $this->title = $title;
        $this->text = $text;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

}