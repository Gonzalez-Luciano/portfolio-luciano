<?php

namespace App\Domain\Publishing;

use DomainException;

final class PublicationValidationException extends DomainException
{
    /** @param list<PublicationIssue> $issues */
    public function __construct(private readonly array $publicationIssues)
    {
        parent::__construct('Publication validation failed.');
    }

    /** @return list<PublicationIssue> */
    public function issues(): array
    {
        return $this->publicationIssues;
    }
}
