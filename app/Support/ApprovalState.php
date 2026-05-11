<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;

class ApprovalState
{
    public static function normalize($value): string
    {
        return trim((string) ($value ?? ''));
    }

    public static function isApprovedValue($value): bool
    {
        $normalized = self::normalize($value);

        if ($normalized === '2') {
            return true;
        }

        return $normalized !== '' && ! in_array($normalized, ['0', '1', '2'], true);
    }

    public static function isApprovalNotRequiredValue($value): bool
    {
        return self::normalize($value) === '0';
    }

    public static function isRejectedValue($value): bool
    {
        return self::normalize($value) === '0';
    }

    public static function isPendingValue($value): bool
    {
        return self::normalize($value) === '1';
    }

    public static function isApprovedRecord($record): bool
    {
        return self::isUsableRecord($record);
    }

    public static function isUsableRecord($record): bool
    {
        $stage1 = self::normalize(data_get($record, 'fapproval'));
        $stage2 = self::normalize(data_get($record, 'fapproval2'));

        if ($stage1 === '' && $stage2 === '') {
            return true;
        }

        return self::isApprovalNotRequiredValue($stage1)
            || self::isApprovedValue($stage1)
            || self::isApprovedValue($stage2);
    }

    public static function hasApprovalProgress($record): bool
    {
        return self::normalize(data_get($record, 'fapproval')) !== ''
            || self::normalize(data_get($record, 'fapproval2')) !== '';
    }

    public static function isEditBlockedRecord($record): bool
    {
        $stage1 = self::normalize(data_get($record, 'fapproval'));
        $stage2 = self::normalize(data_get($record, 'fapproval2'));

        if (self::isApprovedValue($stage1) || self::isApprovedValue($stage2)) {
            return false;
        }

        if ($stage1 === '' && $stage2 === '') {
            return false;
        }

        return in_array($stage1, ['0', '1'], true) || in_array($stage2, ['0', '1'], true);
    }

    public static function isLockedRecord($record): bool
    {
        return self::isEditBlockedRecord($record);
    }

    public static function initializeApprovalColumns(array $recipients, callable $tokenFactory): array
    {
        $stage1 = trim((string) ($recipients[0] ?? ''));
        $stage2 = trim((string) ($recipients[1] ?? ''));

        return [
            'fapproval' => $stage1 !== '' ? '1' : '0',
            'fuserapproved' => null,
            'fdateapproved' => null,
            'fapproval_reason' => null,
            'fapproval_token' => $stage1 !== '' ? $tokenFactory() : null,
            'fapproval2' => $stage2 !== '' ? '1' : null,
            'fuserapproved2' => null,
            'fdateapproved2' => null,
            'fapproval_reason2' => null,
            'fapproval_token2' => $stage2 !== '' ? $tokenFactory() : null,
        ];
    }

    public static function approvedSql(string $prefix = ''): string
    {
        $left = self::qualifiedText($prefix.'fapproval');
        $right = self::qualifiedText($prefix.'fapproval2');

        return "((({$left}) = '' AND ({$right}) = '') OR ({$left}) = '0' OR ({$left}) = '2' OR ({$right}) = '2' OR (({$left}) <> '' AND ({$left}) NOT IN ('0','1','2')) OR (({$right}) <> '' AND ({$right}) NOT IN ('0','1','2')))";
    }

    public static function lockSql(string $prefix = ''): string
    {
        $left = self::qualifiedText($prefix.'fapproval');
        $right = self::qualifiedText($prefix.'fapproval2');

        return "(({$left}) IN ('0', '1') OR ({$right}) IN ('0', '1')) AND ({$left}) <> '2' AND ({$right}) <> '2'";
    }

    public static function applyApprovedFilter(Builder $query, string $prefix = ''): Builder
    {
        return $query->whereRaw(self::approvedSql($prefix));
    }

    private static function qualifiedText(string $column): string
    {
        return "COALESCE(TRIM(CAST({$column} AS TEXT)), '')";
    }
}
