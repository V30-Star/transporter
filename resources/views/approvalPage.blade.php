{{-- resources/views/approvalPage.blade.php --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Approval — {{ $hdr->fprno }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* minimal table polish if you don't use Tailwind for tables */
        .tbl th,
        .tbl td {
            padding: .5rem .75rem;
        }

        .tbl thead th {
            background: #f3f4f6;
            font-weight: 600;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-5xl">
        <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
            <!-- Header bar -->
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <div class="text-xs uppercase tracking-wider text-gray-400">Purchase Request</div>
                    <div class="text-xl font-semibold text-gray-800">Approval — {{ $hdr->fprno }}</div>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500">
                        {{ \Carbon\Carbon::parse($hdr->fprdate)->locale('id')->translatedFormat('d F Y') }}
                    </div>
                </div>
            </div>

            <!-- Info grid -->
            <div class="px-6 py-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded-xl p-4">
                    <div class="text-xs text-gray-500">Supplier</div>
                    <div class="font-medium">{{ $hdr->fsupplier }}</div>
                </div>
                <div class="bg-gray-50 rounded-xl p-4">
                    <div class="text-xs text-gray-500">Branch</div>
                    <div class="font-medium">{{ $hdr->fbranchcode ?? '-' }}</div>
                </div>
                <div class="bg-gray-50 rounded-xl p-4">
                    <div class="text-xs text-gray-500">Tanggal Dibutuhkan</div>
                    <div class="font-medium">
                        {{ $hdr->fneeddate ? \Carbon\Carbon::parse($hdr->fneeddate)->locale('id')->translatedFormat('d F Y') : '-' }}
                    </div>
                </div>
                <div class="bg-gray-50 rounded-xl p-4">
                    <div class="text-xs text-gray-500">Tanggal Paling Lambat</div>
                    <div class="font-medium">
                        {{ $hdr->fduedate ? \Carbon\Carbon::parse($hdr->fduedate)->locale('id')->translatedFormat('d F Y') : '-' }}
                    </div>
                </div>
                @if (!empty($hdr->fket))
                    <div class="md:col-span-2 bg-gray-50 rounded-xl p-4">
                        <div class="text-xs text-gray-500">Catatan</div>
                        <div class="font-medium">{{ $hdr->fket }}</div>
                    </div>
                @endif
            </div>

            <!-- Detail table -->
            <div class="px-6 pb-4">
                <div class="overflow-auto rounded-xl border border-gray-100">
                    <table class="min-w-full tbl">
                        <thead>
                            <tr class="text-left text-sm text-gray-600">
                                <th>Product Code</th>
                                <th>Product Name</th>
                                <th class="text-right">Qty</th>
                                <th>Unit</th>
                                <th class="text-right">Remaining Stock</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm text-gray-800">
                            @forelse ($dt as $item)
                                <tr class="border-t border-gray-100">
                                    <td class="whitespace-nowrap">{{ $item->fprdcode }}</td>
                                    <td class="whitespace-nowrap">{{ $item->product_name ?? '-' }}</td>
                                    <td class="text-right whitespace-nowrap">{{ (int) $item->fqty }}</td>
                                    <td class="whitespace-nowrap">{{ $item->fsatuan }}</td>
                                    <td class="text-right whitespace-nowrap">{{ $item->stock ?? '-' }}</td>
                                    <td class="max-w-[320px]">{{ $item->fdesc ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-gray-500 py-6">Tidak ada detail.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Actions -->
            <div class="px-6 pb-6 pt-2">
                @if ($hdr->fapproval == 1)
                    <form class="flex flex-col md:flex-row gap-3 items-stretch md:items-center" method="POST">
                        @csrf
                        <input type="hidden" name="fprno" value="{{ $hdr->fprno }}">
                        <input type="hidden" name="token" value="{{ request('token') }}">

                        <input type="text" name="note" placeholder="Alasan (opsional untuk Reject)"
                            class="flex-1 border rounded-xl px-3 py-2 focus:outline-none focus:ring w-full" />

                        <button formaction="{{ route('approval.submit', $hdr->fprno) }}"
                            class="px-5 py-2 rounded-xl shadow text-white bg-emerald-600 hover:bg-emerald-700 focus:ring">
                            Approve
                        </button>

                        <button formaction="{{ route('approval.reject', $hdr->fprno) }}" formmethod="POST"
                            class="px-5 py-2 rounded-xl shadow text-white bg-rose-600 hover:bg-rose-700 focus:ring">
                            Reject
                        </button>
                    </form>
                @else
                    <div class="bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-center">
                        @if ($hdr->fapproval == 2)
                            <span class="text-emerald-600 font-semibold">PR ini sudah disetujui.</span>
                        @elseif ($hdr->fapproval == 0)
                            <span class="text-rose-600 font-semibold">PR ini sudah ditolak.</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

</body>

</html>
