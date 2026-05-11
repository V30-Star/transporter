<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $title }} - {{ $documentNo }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-5xl">
        <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <div class="text-xl font-semibold text-gray-800">{{ $title }}</div>
                <div class="text-sm text-gray-500">{{ $documentNo }}</div>
            </div>

            <div class="px-6 py-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($fields as $field)
                    <div class="bg-gray-50 rounded-xl p-4">
                        <div class="text-xs text-gray-500">{{ $field['label'] ?? '-' }}</div>
                        <div class="font-medium">{{ $field['value'] ?? '-' }}</div>
                    </div>
                @endforeach
            </div>

            @if (!empty($detailRows) && count($detailRows) > 0)
                <div class="px-6 pb-4">
                    <div class="overflow-auto rounded-xl border border-gray-100">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-gray-600">
                                <tr>
                                    <th class="px-4 py-3 text-left">Kode</th>
                                    <th class="px-4 py-3 text-left">Nama</th>
                                    <th class="px-4 py-3 text-right">Qty</th>
                                    <th class="px-4 py-3 text-right">Harga</th>
                                    <th class="px-4 py-3 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($detailRows as $row)
                                    <tr class="border-t border-gray-100">
                                        <td class="px-4 py-3">{{ $row->code ?? '-' }}</td>
                                        <td class="px-4 py-3">{{ $row->name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-right">
                                            {{ number_format((float) ($row->qty ?? 0), 2, ',', '.') }}
                                            {{ $row->fsatuan ?? '' }}
                                        </td>
                                        <td class="px-4 py-3 text-right">{{ isset($row->price) ? format_number($row->price) : '-' }}</td>
                                        <td class="px-4 py-3 text-right">{{ isset($row->total) ? format_number($row->total) : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="px-6 pb-6 pt-2">
                @if (!$locked)
                    <form class="flex flex-col md:flex-row gap-3 items-stretch md:items-center" method="POST">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <input type="text" name="note" placeholder="Alasan (opsional untuk Reject)"
                            class="flex-1 border rounded-xl px-3 py-2 focus:outline-none focus:ring w-full" />
                        <button formaction="{{ $approveRoute }}"
                            class="px-5 py-2 rounded-xl shadow text-white bg-emerald-600 hover:bg-emerald-700 focus:ring">
                            Approve
                        </button>
                        <button formaction="{{ $rejectRoute }}" formmethod="POST"
                            class="px-5 py-2 rounded-xl shadow text-white bg-rose-600 hover:bg-rose-700 focus:ring">
                            Reject
                        </button>
                    </form>
                @else
                    <div class="bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-center">
                        @if (\App\Support\ApprovalState::isApprovedRecord($record))
                            <span class="text-emerald-600 font-semibold">{{ $approvedMessage }}</span>
                        @else
                            <span class="text-rose-600 font-semibold">{{ $rejectedMessage }}</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>

</html>
