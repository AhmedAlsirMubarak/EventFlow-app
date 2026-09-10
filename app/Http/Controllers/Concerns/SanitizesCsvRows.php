<?php

namespace App\Http\Controllers\Concerns;

trait SanitizesCsvRows
{
    /**
     * Neutralize CSV formula injection by prefixing values that start with a
     * character a spreadsheet application would interpret as a formula.
     *
     * @param  array<int, mixed>  $row
     * @return array<int, mixed>
     */
    private function sanitizeCsvRow(array $row): array
    {
        return array_map(function ($value) {
            if (! is_string($value) || $value === '') {
                return $value;
            }

            return str_starts_with($value, '=')
                || str_starts_with($value, '+')
                || str_starts_with($value, '-')
                || str_starts_with($value, '@')
                || str_starts_with($value, "\t")
                || str_starts_with($value, "\r")
                ? "'".$value
                : $value;
        }, $row);
    }
}
