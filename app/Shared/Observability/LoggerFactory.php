<?php

declare(strict_types=1);

namespace App\Shared\Observability;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

final class LoggerFactory
{
    public const CHANNEL_APP = 'app';

    public const CHANNEL_DOMAIN = 'domain';

    public const CHANNEL_TELEMETRY_FAILURE = 'telemetry_failure';

    public static function app(): LoggerInterface
    {
        return Log::channel(self::CHANNEL_APP);
    }

    public static function domain(): LoggerInterface
    {
        return Log::channel(self::CHANNEL_DOMAIN);
    }

    public static function telemetryFailure(): LoggerInterface
    {
        return Log::channel(self::CHANNEL_TELEMETRY_FAILURE);
    }
}
