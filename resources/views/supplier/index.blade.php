@extends('layouts.app')

@section('title', 'Master Supplier')

@section('content')
    <div x-data="{
        showDeleteModal: false,
        deleteUrl: null,
        openDelete(url) { this.deleteUrl = url;
            this.showDeleteModal = true },
        closeDelete() { this.showDeleteModal = false;
            this.deleteUrl = null }
    }" class="bg-white rounded shadow p-4">

        {{-- Search (Live) --}}
        <form id="searchForm" method="GET" action="{{ route('supplier.index') }}"
            class="flex flex-wrap justify-between items-center mb-4 gap-2">
            <div class="flex items-center space-x-2 w-full">
                <label class="font-semibold">Search:</label>
                <input id="searchInput" type="text" name="search" value="{{ $search }}"
                    class="border rounded px-2 py-1 w-1/4" placeholder="Cari...">
                <button type="submit" class="hidden">Cari</button>
            </div>
        </form>

        @php
            $showActionsColumn = ($canEdit ?? false) || ($canDelete ?? false);
        @endphp

        {{-- Table --}}
        <table class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-1">Kode Supplier</th>
                    <th class="border px-2 py-1">Nama Supplier</th>
                    <th class="border px-2 py-1">Kontak</th>
                    <th class="border px-2 py-1">Telepon</th>
                    <th class="border px-2 py-1">Alamat</th>
                    <th class="border px-2 py-1">Kota</th>
                    @if ($showActionsColumn)
                        <th class="border px-2 py-1">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody id="tableBody">
                @forelse($suppliers as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="border px-2 py-1">{{ $item->fsuppliercode }}</td>
                        <td class="border px-2 py-1">{{ $item->fsuppliername }}</td>
                        <td class="border px-2 py-1">{{ $item->fkontakperson ?? '-' }}</td>
                        <td class="border px-2 py-1">{{ $item->ftelp ?? '-' }}</td>
                        <td class="border px-2 py-1">{{ $item->faddress ?? '-' }}</td>
                        <td class="border px-2 py-1">{{ $item->fcity ?? '-' }}</td>

                        @if ($showActionsColumn)
                            <td class="border px-2 py-1 space-x-2">
                                @if ($canEdit)
                                    <a href="{{ route('supplier.edit', $item->fsupplierid) }}">
                                        <button
                                            class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                                            <x-heroicon-o-pencil-square class="w-4 h-4 mr-1" /> Edit
                                        </button>
                                    </a>
                                @endif
                                @if ($canDelete)
                                    <button @click="openDelete('{{ route('supplier.destroy', $item->fsupplierid) }}')"
                                        class="inline-flex items-center bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                                        <x-heroicon-o-trash class="w-4 h-4 mr-1" /> Hapus
                                    </button>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $showActionsColumn ? 7 : 6 }}" class="text-center py-4">Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Modal Delete --}}
        <div x-show="showDeleteModal" x-cloak
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div @click.away="closeDelete()" class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Konfirmasi Hapus</h3>
                <p class="mb-6">Apakah Anda yakin ingin menghapus data ini?</p>

                <div class="flex justify-end space-x-2">
                    <button @click="closeDelete()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Batal</button>
                    <form :action="deleteUrl" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Hapus</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Bottom actions & AJAX pagination --}}
        <div id="pagination" class="mt-4 flex justify-between items-center">
            <div class="space-x-2">
                @if ($canCreate ?? false)
                    <a href="{{ route('supplier.create') }}"
                        class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        <x-heroicon-o-plus class="w-4 h-4 mr-1" /> Baru
                    </a>
                @endif
            </div>

            <div class="flex items-center space-x-2">
                <button id="prevBtn"
                    class="px-3 py-1 rounded border hover:bg-gray-100 {{ $suppliers->onFirstPage() ? 'opacity-50' : '' }}"
                    {{ $suppliers->onFirstPage() ? 'disabled' : '' }}
                    data-page="{{ $suppliers->previousPageUrl() ?? '' }}">&larr;</button>

                <span id="pageInfo" class="text-sm">
                    Page {{ $suppliers->currentPage() }} of {{ $suppliers->lastPage() }}
                </span>

                <button id="nextBtn"
                    class="px-3 py-1 rounded border hover:bg-gray-100 {{ $suppliers->hasMorePages() ? '' : 'opacity-50' }}"
                    {{ $suppliers->hasMorePages() ? '' : 'disabled' }}
                    data-page="{{ $suppliers->nextPageUrl() ?? '' }}">&rarr;</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const form = document.getElementById('searchForm');
            const input = document.getElementById('searchInput');
            const tbody = document.getElementById('tableBody');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const pageInfo = document.getElementById('pageInfo');

            let timer = null,
                lastAbort = null;

            // perms awal dari server (dipakai saat render AJAX)
            let perms = {
                can_edit: {!! json_encode($canEdit ?? false) !!},
                can_delete: {!! json_encode($canDelete ?? false) !!}
            };

            // buka modal delete dari row yang di-render via JS
            window.openDeleteModal = function(url) {
                // pakai event custom kalau mau di-handle global; di sini langsung set Alpine method:
                document.querySelector('[x-data]').__x.$data.openDelete(url);
            };

            function aksiButtons(item) {
                let html = '';
                if (perms.can_edit) {
                    html += `<a href="${item.edit_url}"
                       class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                        Edit
                     </a>`;
                }
                if (perms.can_delete) {
                    html += `<button onclick="window.openDeleteModal('${item.destroy_url}')"
                          class="inline-flex items-center bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 ml-2">
                        Hapus
                     </button>`;
                }
                return html;
            }

            function rowHtml(item) {
                const actions = aksiButtons(item);
                const showAksi = (perms.can_edit || perms.can_delete);
                return `
        <tr class="hover:bg-gray-50">
            <td class="border px-2 py-1">${item.fsuppliercode ?? ''}</td>
            <td class="border px-2 py-1">${item.fsuppliername ?? ''}</td>
            <td class="border px-2 py-1">${item.fkontakperson ?? '-'}</td>
            <td class="border px-2 py-1">${item.fphone ?? '-'}</td>
            <td class="border px-2 py-1">${item.faddress ?? '-'}</td>
            <td class="border px-2 py-1">${item.fcity ?? '-'}</td>
            ${ showAksi ? `<td class="border px-2 py-1">${actions}</td>` : '' }
        </tr>`;
            }

            function render(json) {
                if (!json || !json.data) return;

                // sync perms kalau server ngirim (antisipasi perubahan session)
                if (json.perms) perms = json.perms;

                if (json.data.length === 0) {
                    const colCount = document.querySelector('thead tr').children.length;
                    tbody.innerHTML =
                    `<tr><td colspan="${colCount}" class="text-center py-4">Tidak ada data.</td></tr>`;
                } else {
                    tbody.innerHTML = json.data.map(rowHtml).join('');
                }

                // update pagination
                prevBtn.dataset.page = json.links.prev || '';
                nextBtn.dataset.page = json.links.next || '';
                prevBtn.disabled = !json.links.prev;
                nextBtn.disabled = !json.links.next;
                prevBtn.classList.toggle('opacity-50', !json.links.prev);
                nextBtn.classList.toggle('opacity-50', !json.links.next);
                pageInfo.textContent = `Page ${json.links.current_page} of ${json.links.last_page}`;
            }

            function fetchTable(url) {
                if (lastAbort) lastAbort.abort();
                lastAbort = new AbortController();

                fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        signal: lastAbort.signal
                    })
                    .then(r => r.json())
                    .then(render)
                    .catch(err => {
                        if (err.name !== 'AbortError') console.error(err);
                    });
            }

            function buildUrl(baseUrl = null) {
                if (baseUrl) {
                    const u = new URL(baseUrl, window.location.origin);
                    u.searchParams.set('search', input.value || '');
                    return u.toString();
                }
                const base = form.getAttribute('action');
                const params = new URLSearchParams(new FormData(form));
                params.delete('page'); // reset ke page 1 saat ketik
                return `${base}?${params.toString()}`;
            }

            // live search (debounce)
            input.addEventListener('input', () => {
                clearTimeout(timer);
                timer = setTimeout(() => fetchTable(buildUrl()), 300);
            });
            // cegah enter submit
            input.addEventListener('keydown', e => {
                if (e.key === 'Enter') e.preventDefault();
            });

            // pagination ajax
            document.getElementById('pagination')?.addEventListener('click', e => {
                if (e.target.tagName === 'BUTTON' && e.target.dataset.page) {
                    e.preventDefault();
                    fetchTable(buildUrl(e.target.dataset.page));
                }
            });
        })();
    </script>
@endpush
