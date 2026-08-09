<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Data;

use Illuminate\Support\Carbon;

class ParsedLogEntry
{
    public function __construct(
        public readonly string $level,
        public readonly string $message,
        public readonly ?string $context,
        public readonly ?string $stackTrace,
        public readonly ?Carbon $occurredAt,
        public readonly ?string $exceptionClass,
    ) {
    }
}
