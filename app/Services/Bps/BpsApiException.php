<?php

namespace App\Services\Bps;

use RuntimeException;
use Throwable;

class BpsApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $httpStatus = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
