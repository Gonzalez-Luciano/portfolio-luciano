<?php

namespace App\Domain\Publishing;

final readonly class PublicationIssue
{
    public function __construct(
        public string $code,
        public string $path,
        public string $message,
    ) {}
}
