<?php

namespace Modules\Vendor\Services;

/** A departure change the rules refuse. `errorCode` is stable and shown by the API. */
class DepartureRuleException extends \DomainException
{
    public function __construct(public readonly string $errorCode, string $message, public readonly int $status = 409)
    {
        parent::__construct($message);
    }
}
