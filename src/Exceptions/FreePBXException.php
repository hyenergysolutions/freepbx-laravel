<?php

declare(strict_types=1);

namespace HyEnergySolutions\FreePBX\Exceptions;

use Exception;

class FreePBXException extends Exception
{
    public static function tokenFailed(string $body): self
    {
        return new self("Failed to get FreePBX token: {$body}");
    }

    public static function graphqlError(string $body): self
    {
        return new self("FreePBX GraphQL error: {$body}");
    }

    public static function graphqlValidationErrors(array $errors): self
    {
        return new self('FreePBX GraphQL error: '.json_encode($errors));
    }

    public static function restError(string $body): self
    {
        return new self("FreePBX REST API error: {$body}");
    }
}
