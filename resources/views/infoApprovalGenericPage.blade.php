<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-xl bg-white rounded-2xl shadow-xl p-6">
        <div class="text-xl font-semibold text-gray-800">{{ $title }}</div>
        <div class="text-sm text-gray-500 mt-1">{{ $documentNo }}</div>

        <div class="mt-6">
            @if (\App\Support\ApprovalState::isApprovedRecord($record))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                    <div class="font-semibold text-emerald-700">Dokumen sudah disetujui.</div>
                    <div class="mt-2 text-sm text-emerald-700">
                        @if (!empty($record->fuserapproved) && \App\Support\ApprovalState::isApprovedValue($record->fapproval ?? null))
                            Disetujui oleh: {{ $record->fuserapproved }}
                        @elseif (!empty($record->fuserapproved2) && \App\Support\ApprovalState::isApprovedValue($record->fapproval2 ?? null))
                            Disetujui oleh: {{ $record->fuserapproved2 }}
                        @else
                            Disetujui melalui link approval.
                        @endif
                    </div>
                </div>
            @else
                <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
                    <div class="font-semibold text-rose-700">Dokumen ditolak atau belum selesai di-approve.</div>
                    @if (!empty($record->fapproval_reason) || !empty($record->fapproval_reason2))
                        <div class="mt-2 text-sm text-rose-700">
                            Alasan:
                            {{ $record->fapproval_reason ?: $record->fapproval_reason2 }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</body>

</html>
