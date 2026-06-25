<div class="flex justify-end gap-1.5 flex-nowrap">
    <a href="{{ route('lembarpenagihan.view', $row->ftagihanid) }}">
        <button class="inline-flex items-center bg-slate-500 text-white px-3 py-1.5 text-xs rounded hover:bg-slate-600">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
            View
        </button>
    </a>
    @if ($canEdit)
        <a href="{{ route('lembarpenagihan.edit', $row->ftagihanid) }}">
            <button class="inline-flex items-center bg-yellow-500 text-white px-3 py-1.5 text-xs rounded hover:bg-yellow-600">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Edit
            </button>
        </a>
    @endif
    @if ($canDelete)
        <a href="{{ route('lembarpenagihan.delete', $row->ftagihanid) }}">
            <button class="inline-flex items-center bg-red-600 text-white px-3 py-1.5 text-xs rounded hover:bg-red-700">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                Hapus
            </button>
        </a>
    @endif
</div>
