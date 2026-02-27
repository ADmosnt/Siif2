<?php

namespace App\Exceptions;

use Exception;

class Duplicidad extends Exception
{
    public int $rowNumber;

    public function __construct(string $message, int $rowNumber)
    {
        parent::__construct($message);
        $this->rowNumber = $rowNumber;
    }
}