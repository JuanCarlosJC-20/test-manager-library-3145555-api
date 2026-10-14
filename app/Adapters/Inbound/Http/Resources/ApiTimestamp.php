<?php

namespace App\Adapters\Inbound\Http\Resources;

use DateTimeImmutable;
use DateTimeZone;

final class ApiTimestamp
{
    public static function format(DateTimeImmutable $value): string
    {
        return $value->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }
}
