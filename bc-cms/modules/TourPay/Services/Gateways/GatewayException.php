<?php

namespace Modules\TourPay\Services\Gateways;

/** The gateway refused, or could not be reached. The message is safe to show a guest or a vendor. */
class GatewayException extends \RuntimeException
{
    public function __construct(public readonly string $errorCode, string $message)
    {
        parent::__construct($message);
    }
}
