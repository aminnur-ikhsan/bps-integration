<?php

namespace App\Services\Bps;

use RuntimeException;

class BpsApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $httpStatus = null,
        public readonly ?string $cause = null,
    ) {
        parent::__construct($message);
    }
}
