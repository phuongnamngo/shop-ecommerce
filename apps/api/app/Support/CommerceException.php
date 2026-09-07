<?php

namespace App\Support;

final class CommerceException extends \RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly ?string $field = null,
        public readonly int $status = 422,
    ) {
        parent::__construct($message);
    }
}
