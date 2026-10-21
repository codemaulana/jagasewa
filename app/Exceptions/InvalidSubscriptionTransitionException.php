<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidSubscriptionTransitionException extends RuntimeException
{
    public function __construct(string $from, string $to, string $trigger)
    {
        parent::__construct("Invalid subscription transition from [{$from}] to [{$to}] via trigger [{$trigger}].");
    }
}
