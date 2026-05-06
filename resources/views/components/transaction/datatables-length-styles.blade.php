@props([
    'tables' => [],
])

@php
    $tables = array_values(array_filter(array_map('trim', (array) $tables)));
@endphp

<style>
@foreach ($tables as $tableId)
    div#{{ $tableId }}_length select,
    .dataTables_wrapper #{{ $tableId }}_length select,
    table#{{ $tableId }}+.dataTables_wrapper .dataTables_length select {
        min-width: 140px !important;
        width: auto !important;
        padding: 8px 45px 8px 16px !important;
        font-size: 14px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem !important;
    }

    div#{{ $tableId }}_length,
    .dataTables_wrapper #{{ $tableId }}_length,
    .dataTables_wrapper .dataTables_length {
        min-width: 250px !important;
    }

    div#{{ $tableId }}_length label,
    .dataTables_wrapper #{{ $tableId }}_length label,
    .dataTables_wrapper .dataTables_length label {
        font-size: 14px !important;
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
    }
@endforeach
</style>
