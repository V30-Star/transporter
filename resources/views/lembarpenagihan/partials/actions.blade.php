<div class="inline-flex gap-1">
    <a href="{{ route('lembarpenagihan.view', $row->fstockmtid) }}" class="px-2 py-1 bg-gray-100 rounded hover:bg-gray-200">View</a>
    <a href="{{ route('lembarpenagihan.edit', $row->fstockmtid) }}" class="px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">Edit</a>
    <a href="{{ route('lembarpenagihan.delete', $row->fstockmtid) }}" class="px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700">Delete</a>
</div>
