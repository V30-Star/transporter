{{-- resources/views/infoApprovalPage.blade.php --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Info Approval{{ $pr->fprno }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="flex justify-center mt-10">
        <div class="w-full max-w-md">
            <div class="bg-white shadow-md rounded-lg p-6 text-center">
                @if ($pr->fapproval == 2)
                    <div class="mb-4">
                        <svg class="mx-auto h-16 w-16 text-green-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-green-600">PR Disetujui</h2>
                    <p class="mt-2 text-gray-600">Nomor PR: <span class="font-medium">{{ $pr->fprno }}</span></p>
                    <p class="mt-1 text-gray-600">Disetujui oleh: {{ $pr->fuserapproved }}</p>
                    <p class="mt-1 text-gray-500 text-sm">{{ $pr->fdateapproved }}</p>
                @elseif ($pr->fapproval == 0)
                    <div class="mb-4">
                        <svg class="mx-auto h-16 w-16 text-red-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-red-600">PR Ditolak</h2>
                    <p class="mt-2 text-gray-600">Nomor PR: <span class="font-medium">{{ $pr->fprno }}</span></p>
                    <p class="mt-1 text-gray-600">Ditolak oleh: {{ $pr->fuserapproved }}</p>
                    <p class="mt-1 text-gray-600">Alasan: <span class="italic">{{ $pr->fapproval_reason }}</span></p>
                    <p class="mt-1 text-gray-500 text-sm">{{ $pr->fdateapproved }}</p>
                @endif
            </div>
        </div>
    </div>
</body>

</html>
