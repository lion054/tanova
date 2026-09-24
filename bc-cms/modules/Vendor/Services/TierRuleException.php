<?php

namespace Modules\Vendor\Services;

/** A tier the rules refuse. `errorCode` is stable and shown by the API. */
class TierRuleException extends \DomainException
{
    public function __construct(public readonly string $errorCode, string $message)
    {
        parent::__construct($message);
    }
}
