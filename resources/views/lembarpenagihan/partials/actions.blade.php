<div class="inline-flex gap-1">
    <a href="{{ route('lembarpenagihan.view', $row->ftagihanid) }}" class="px-2 py-1 bg-gray-100 rounded hover:bg-gray-200">View</a>
    @if ($canEdit)
        <a href="{{ route('lembarpenagihan.edit', $row->ftagihanid) }}" class="px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">Edit</a>
    @endif
    @if ($canDelete)
        <a href="{{ route('lembarpenagihan.delete', $row->ftagihanid) }}" class="px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700">Delete</a>
    @endif
</div>
