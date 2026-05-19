@php
    $action = 'view';
    $isUsageLocked = $isUsageLocked ?? false;
    $usageLockMessage = $usageLockMessage ?? null;
@endphp

@include('mutasi.edit')
