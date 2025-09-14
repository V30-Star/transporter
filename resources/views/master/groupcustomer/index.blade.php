@extends('layouts.app')

@section('title', 'Daftar Group Customer')

@section('content')
    <div x-data="{
        showDeleteModal: false,
        deleteUrl: null,
        openDelete(url) {
            this.deleteUrl = url;
            this.showDeleteModal = true
        },
        closeDelete() {
            this.deleteUrl = null;
            this.showDeleteModal = false
        }
    }" x-on:open-delete.window="openDelete($event.detail)" class="bg-white rounded shadow p-4">

        {{-- Search --}}
        <form id="searchForm" method="GET" action="{{ route('groupcustomer.index') }}"
            class="flex flex-wrap justify-between items-center mb-4 gap-2">
            <div class="flex items-center space-x-2 w-full">
                <label class="font-semibold">Search:</label>
                <input id="searchInput" type="text" name="search" value="{{ $search }}"
                    class="border rounded px-2 py-1 w-1/4" placeholder="Cari Group Customer...">
                <button type="submit" class="hidden">Cari</button>
            </div>
        </form>

        @php
            $showActionsColumn = $canEdit || $canDelete;
        @endphp

        <table class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-1 cursor-pointer sortCol" data-sort-by="fgroupcode">
                        <div class="flex items-center gap-1">
                            <span>Kode Group</span>
                            <span id="icon-fgroupcode" class="text-xs opacity-50">↕</span>
                        </div>
                    </th>
                    <th class="border px-2 py-1 cursor-pointer sortCol" data-sort-by="fgroupname">
                        <div class="flex items-center gap-1">
                            <span>Nama Group</span>
                            <span id="icon-fgroupname" class="text-xs opacity-50">↕</span>
                        </div>
                    </th>
                    @if ($showActionsColumn)
                        <th class="border px-2 py-1">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody id="tableBody">
                @forelse($groupCustomers as $gc)
                    <tr class="hover:bg-gray-50">
                        <td class="border px-2 py-1">{{ $gc->fgroupcode }}</td>
                        <td class="border px-2 py-1">{{ $gc->fgroupname }}</td>
                        @if ($showActionsColumn)
                            <td class="border px-2 py-1 space-x-2">
                                @if ($canEdit)
                                    <a href="{{ route('groupcustomer.edit', $gc->fgroupid) }}"
                                        class="inline-flex items-center bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">
                                        <x-heroicon-o-pencil-square class="w-4 h-4 mr-1" /> Edit
                                    </a>
                                @endif
                                @if ($canDelete)
                                    <button
                                        onclick="window.openDeleteModal('{{ route('groupcustomer.destroy', $gc->fgroupid) }}')"
                                        class="inline-flex items-center bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">
                                        <x-heroicon-o-trash class="w-4 h-4 mr-1" /> Hapus
                                    </button>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $showActionsColumn ? 3 : 2 }}" class="text-center py-4">Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        <div id="pagination" class="mt-4 flex justify-between items-center">
            <div class="space-x-2">
                @if ($canCreate)
                    <a href="{{ route('groupcustomer.create') }}"
                        class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        <x-heroicon-o-plus class="w-4 h-4 mr-1" /> Baru
                    </a>
                @endif
            </div>
            <div class="flex items-center space-x-2">
                <button id="prevBtn"
                    class="px-3 py-1 rounded border hover:bg-gray-100 {{ $groupCustomers->onFirstPage() ? 'opacity-50' : '' }}"
                    {{ $groupCustomers->onFirstPage() ? 'disabled' : '' }}
                    data-page="{{ $groupCustomers->previousPageUrl() ?? '' }}">&larr;</button>
                <span id="pageInfo" class="text-sm">Page {{ $groupCustomers->currentPage() }} of
                    {{ $groupCustomers->lastPage() }}</span>
                <button id="nextBtn"
                    class="px-3 py-1 rounded border hover:bg-gray-100 {{ $groupCustomers->hasMorePages() ? '' : 'opacity-50' }}"
                    {{ $groupCustomers->hasMorePages() ? '' : 'disabled' }}
                    data-page="{{ $groupCustomers->nextPageUrl() ?? '' }}">&rarr;</button>
            </div>
        </div>

        {{-- Modal Hapus --}}
        <div x-show="showDeleteModal" x-cloak
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div @click.away="closeDelete()" class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Konfirmasi Hapus</h3>
                <p class="mb-6">Yakin mau hapus data ini?</p>
                <div class="flex justify-end space-x-2">
                    <button @click="closeDelete()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Batal</button>
                    <form :action="deleteUrl" method="POST" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit"
                            class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Hapus</button>
                    </form>
                </div>
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

            // permissions awal (bisa di-override dari JSON)
            let perms = {
                can_edit: {!! json_encode($canEdit) !!},
                can_delete: {!! json_encode($canDelete) !!}
            };

            // state sorting (fallback dari server)
            const sortState = {
                by: {!! isset($sortBy) ? json_encode($sortBy) : '"fgroupid"' !!},
                dir: {!! isset($sortDir) ? json_encode($sortDir) : '"desc"' !!}
            };

            window.openDeleteModal = url => {
                window.dispatchEvent(new CustomEvent('open-delete', {
                    detail: url
                }));
            };

            function rowHtml(gc) {
                let actions = '';
                if (perms.can_edit) {
                    actions +=
                        `<a href="${gc.edit_url}"
                class="inline-flex items-center bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">Edit</a>`;
                }
                if (perms.can_delete) {
                    actions +=
                        `<button onclick="window.openDeleteModal('${gc.destroy_url}')"
                class="inline-flex items-center bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 ml-2">Hapus</button>`;
                }
                return `<tr class="hover:bg-gray-50">
            <td class="border px-2 py-1">${gc.fgroupcode ?? ''}</td>
            <td class="border px-2 py-1">${gc.fgroupname ?? ''}</td>
            ${actions ? `<td class="border px-2 py-1">${actions}</td>` : ''}
        </tr>`;
            }

            function applySortIcons() {
                ['fgroupcode', 'fgroupname'].forEach(col => {
                    const el = document.getElementById('icon-' + col);
                    if (!el) return;
                    el.textContent = '↕';
                    el.classList.add('opacity-50');
                });
                const active = document.getElementById('icon-' + sortState.by);
                if (active) {
                    active.textContent = (sortState.dir === 'asc') ? '↑' : '↓';
                    active.classList.remove('opacity-50');
                }
            }

            function render(json) {
                if (!json || !json.data) return;
                if (json.perms) perms = json.perms;

                tbody.innerHTML = json.data.length ?
                    json.data.map(rowHtml).join('') :
                    `<tr><td colspan="3" class="text-center py-4">Tidak ada data.</td></tr>`;

                prevBtn.dataset.page = json.links.prev || '';
                nextBtn.dataset.page = json.links.next || '';
                prevBtn.disabled = !json.links.prev;
                nextBtn.disabled = !json.links.next;
                prevBtn.classList.toggle('opacity-50', !json.links.prev);
                nextBtn.classList.toggle('opacity-50', !json.links.next);
                pageInfo.textContent = `Page ${json.links.current_page} of ${json.links.last_page}`;

                if (json.sort && json.sort.by) {
                    sortState.by = json.sort.by;
                    sortState.dir = json.sort.dir || 'desc';
                }
                applySortIcons();

                // opsional: update URL address bar (tanpa reload)
                const qs = new URLSearchParams(new FormData(form));
                qs.set('page', json.links.current_page);
                qs.set('sort_by', sortState.by);
                qs.set('sort_dir', sortState.dir);
                history.replaceState({}, '', `${form.action}?${qs.toString()}`);
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
                    .catch(e => {
                        if (e.name !== 'AbortError') console.error(e)
                    });
            }

            function buildUrl(baseUrl = null) {
                const base = baseUrl ? new URL(baseUrl, window.location.origin) :
                    new URL(form.getAttribute('action'), window.location.origin);
                base.searchParams.set('search', input?.value || '');
                base.searchParams.set('sort_by', sortState.by);
                base.searchParams.set('sort_dir', sortState.dir);
                if (!baseUrl) base.searchParams.delete('page'); // reset page kalau bukan pagination
                return base.toString();
            }

            // live search (debounce)
            input.addEventListener('input', () => {
                clearTimeout(timer);
                timer = setTimeout(() => fetchTable(buildUrl()), 300);
            });
            input.addEventListener('keydown', e => {
                if (e.key === 'Enter') e.preventDefault();
            });

            // klik header → toggle sort
            document.querySelectorAll('.sortCol').forEach(th => {
                th.addEventListener('click', () => {
                    const col = th.dataset.sortBy;
                    if (!col) return;
                    if (sortState.by === col) {
                        sortState.dir = (sortState.dir === 'asc') ? 'desc' : 'asc';
                    } else {
                        sortState.by = col;
                        sortState.dir = 'asc';
                    }
                    applySortIcons();
                    fetchTable(buildUrl());
                });
            });

            // pagination ajax
            document.getElementById('pagination')?.addEventListener('click', e => {
                if (e.target.tagName === 'BUTTON' && e.target.dataset.page) {
                    e.preventDefault();
                    fetchTable(buildUrl(e.target.dataset.page));
                }
            });

            // init ikon
            applySortIcons();
        })();
    </script>
@endpush
