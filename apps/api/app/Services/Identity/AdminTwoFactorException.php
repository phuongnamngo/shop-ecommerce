<?php

namespace App\Services\Identity;

use RuntimeException;

final class AdminTwoFactorException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        public readonly int $status,
    ) {
        parent::__construct($errorCode);
    }
}
