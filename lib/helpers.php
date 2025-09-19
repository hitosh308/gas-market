<?php

declare(strict_types=1);

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function textareaLinesToArray(string $value): array
{
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    $lines = array_map('trim', explode("\n", $value));

    return array_values(array_filter($lines, static fn ($line) => $line !== ''));
}

function commaListToArray(string $value): array
{
    $parts = array_map('trim', explode(',', $value));

    return array_values(array_filter($parts, static fn ($part) => $part !== ''));
}

function arrayToTextarea(array $items): string
{
    return implode("\n", $items);
}

function arrayToCommaList(array $items): string
{
    return implode(', ', $items);
}

function formatDate(?string $date): string
{
    if (!$date) {
        return '';
    }

    $timestamp = strtotime($date);

    return $timestamp ? date('Y年n月j日', $timestamp) : $date;
}
