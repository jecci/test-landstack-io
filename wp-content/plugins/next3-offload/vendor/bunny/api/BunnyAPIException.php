<?php
namespace Next3Bunny\BunnyCdn;

use \Exception;

class BunnyAPIException extends \Exception
{
    public function errorMessage(): string
    {
        return "Error on line {$this->getLine()} in {$this->getFile()}. {$this->getMessage()}.";
    }
}