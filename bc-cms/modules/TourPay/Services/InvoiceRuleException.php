<?php

namespace Modules\TourPay\Services;

/** A refused change, with a stable code the API can return. */
class InvoiceRuleException extends \DomainException
{
    public function __construct(public readonly string $errorCode, string $message)
    {
        parent::__construct($message);
    }
}
