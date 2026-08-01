<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;

/**
 * Simple approval helper.
 *
 * fapproval semantics:
 *   0  = belum disetujui (not approved)
 *   1  = sudah disetujui (approved)
 */
class ApprovalState
{
    public static function normalize($value): string
    {
        return trim((string) ($value ?? ''));
    }

    /** Returns true when the record's fapproval is 1 (approved). */
    public static function isApprovedRecord($record): bool
    {
        return self::normalize(data_get($record, 'fapproval')) === '1';
    }

    /** Alias for isApprovedRecord – kept for call-site compatibility. */
    public static function isUsableRecord($record): bool
    {
        return self::isApprovedRecord($record);
    }

    /** A record blocks editing when it is already approved (fapproval = 1). */
    public static function isEditBlockedRecord($record): bool
    {
        return self::isApprovedRecord($record);
    }

    /** Alias for isEditBlockedRecord. */
    public static function isLockedRecord($record): bool
    {
        return self::isEditBlockedRecord($record);
    }

    /** Returns true when fapproval = 0 (pending / not yet approved). */
    public static function isPendingValue($value): bool
    {
        return self::normalize($value) === '0';
    }

    /** Returns true when fapproval = 1 (approved). */
    public static function isApprovedValue($value): bool
    {
        return self::normalize($value) === '1';
    }

    /** Returns true when fapproval = 0 (treated as "rejected / not approved"). */
    public static function isRejectedValue($value): bool
    {
        return self::normalize($value) === '0';
    }

    /** Returns true when an approval value has been set (not null / empty). */
    public static function hasApprovalProgress($record): bool
    {
        return self::normalize(data_get($record, 'fapproval')) !== '';
    }

    /**
     * Raw SQL snippet that resolves to true when a row is approved.
     * $prefix should end with a dot, e.g. 'tr_prh.' or 'mt.'.
     */
    public static function approvedSql(string $prefix = ''): string
    {
        return "COALESCE(CAST({$prefix}fapproval AS TEXT), '') = '1'";
    }

    /**
     * Apply an "approved only" WHERE clause to the given query.
     *
     * Usage:  ApprovalState::applyApprovedFilter($query, 'tr_prh.');
     */
    public static function applyApprovedFilter(EloquentBuilder|Builder $query, string $prefix = ''): EloquentBuilder|Builder
    {
        return $query->where("{$prefix}fapproval", 1);
    }
}
