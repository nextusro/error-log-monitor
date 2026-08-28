<?php

declare(strict_types=1);

namespace Nextus\ErrorLogMonitor\Services;

class NormalizationTemplateCompiler
{
    public function normalize(string $message, string $template): string
    {
        $template = trim($template);
        $pattern = preg_quote($template, '~');
        $pattern = str_replace([
            '\{number\}',
            '\{uuid\}',
            '\{hex\}',
            '\{value\}',
        ], [
            '\d+',
            '[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[1-5][0-9a-fA-F]{3}\-[89abAB][0-9a-fA-F]{3}\-[0-9a-fA-F]{12}',
            '(?:0x)?[0-9a-fA-F]+',
            '.+?',
        ], $pattern);

        return preg_match('~^'.$pattern.'$~u', trim($message)) === 1
            ? $template
            : $message;
    }
}
