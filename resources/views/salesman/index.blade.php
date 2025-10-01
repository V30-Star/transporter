@extends('layouts.app')

@section('title', 'Master Salesman')

@section('content')
    <div x-data="{
        showDeleteModal: false,
        deleteUrl: null,
        openDelete(url) {
            this.deleteUrl = url;
            this.showDeleteModal = true
        },
        closeDelete() {
            this.showDeleteModal = false;
            this.deleteUrl = null
        }
    }" x-on:open-delete.window="openDelete($event.detail)" class="bg-white rounded shadow p-4">
        {{-- Search Live (AJAX) --}}
        <form id="searchForm" method="GET" action="{{ route('salesman.index') }}"
            class="flex flex-wrap justify-between items-center mb-4 gap-2">
            <div class="flex items-center space-x-2 w-full">
                <label class="font-semibold">Search:</label>
                <input id="searchInput" type="text" name="search" value="{{ $search }}"
                    class="border rounded px-2 py-1 w-1/4" placeholder="Cari...">
                <button type="submit" class="hidden">Cari</button>

                {{-- NEW: Status Filter --}}
                <select id="statusFilter" name="status" class="border rounded px-2 py-1">
                    <option value="all">All</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="nonactive" {{ request('status') === 'nonactive' ? 'selected' : '' }}>No Active</option>
                </select>
            </div>
        </form>

        @php
            $canCreate = in_array('createSalesman', explode(',', session('user_restricted_permissions', '')));
            $canEdit = in_array('updateSalesman', explode(',', session('user_restricted_permissions', '')));
            $canDelete = in_array('deleteSalesman', explode(',', session('user_restricted_permissions', '')));
            $showActionsColumn = $canEdit || $canDelete;
        @endphp

        {{-- Table --}}
        <table class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-1 cursor-pointer sortCol" data-sort-by="fsalesmancode">
                        <div class="flex items-center gap-1">
                            <span>Kode Salesman</span>
                            <span id="icon-fsalesmancode" class="text-lg font-semibold text-green-600">⇅</span>
                        </div>
                    </th>
                    <th class="border px-2 py-1 cursor-pointer sortCol" data-sort-by="fsalesmanname">
                        <div class="flex items-center gap-1">
                            <span>Nama Salesman</span>
                            <span id="icon-fsalesmanname" class="text-lg font-semibold text-green-600">⇅</span>
                        </div>
                    </th>
                    <th class="border px-2 py-1 cursor-pointer sortCol" data-sort-by="fsalesmanname">
                        <div class="flex items-center gap-1">
                            <span>Status</span>
                            <span id="icon-fsalesmanname" class="text-lg font-semibold text-green-600">⇅</span>
                        </div>
                    </th>
                    @if ($showActionsColumn)
                        <th class="border px-2 py-1">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody id="tableBody">
                @forelse($salesmen as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="border px-2 py-1">{{ $item->fsalesmancode }}</td>
                        <td class="border px-2 py-1">{{ $item->fsalesmanname }}</td>
                        <td class="border px-2 py-1">
                            @if ($item->fnonactive == 1)
                                Non Active
                            @else
                                Active
                            @endif
                        </td>
                        @if ($showActionsColumn)
                            <td class="border px-2 py-1 space-x-2">
                                @if ($canEdit)
                                    <a href="{{ route('salesman.edit', $item->fsalesmanid) }}">
                                        <button
                                            class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                                            <x-heroicon-o-pencil-square class="w-4 h-4 mr-1" />
                                            Edit
                                        </button>
                                    </a>
                                @endif

                                @if ($canDelete)
                                    <button
                                        @click="$dispatch('open-delete', '{{ route('salesman.destroy', $item->fsalesmanid) }}')"
                                        class="inline-flex items-center bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                                        <x-heroicon-o-trash class="w-4 h-4 mr-1" />
                                        Hapus
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

        {{-- Bottom actions + Pagination (AJAX aware) --}}
        <div id="pagination" class="mt-4 flex justify-between items-center">
            <div class="space-x-2">
                @if ($canCreate)
                    <a href="{{ route('salesman.create') }}"
                        class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        <x-heroicon-o-plus class="w-4 h-4 mr-1" />
                        Baru
                    </a>
                @endif
            </div>

            <div class="flex items-center space-x-2">
                <button id="prevBtn"
                    class="px-3 py-1 rounded border hover:bg-gray-100 {{ $salesmen->onFirstPage() ? 'opacity-50' : '' }}"
                    {{ $salesmen->onFirstPage() ? 'disabled' : '' }}
                    data-page="{{ $salesmen->previousPageUrl() ?? '' }}">&larr;</button>

                <span id="pageInfo" class="text-sm">
                    Page {{ $salesmen->currentPage() }} of {{ $salesmen->lastPage() }}
                </span>

                <button id="nextBtn"
                    class="px-3 py-1 rounded border hover:bg-gray-100 {{ $salesmen->hasMorePages() ? '' : 'opacity-50' }}"
                    {{ $salesmen->hasMorePages() ? '' : 'disabled' }}
                    data-page="{{ $salesmen->nextPageUrl() ?? '' }}">&rarr;</button>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        (function() {
            const form = document.getElementById('searchForm');
            const input = document.getElementById('searchInput');
            const filter = document.getElementById('filterBy');
            const statusFilter = document.getElementById('statusFilter');
            const tbody = document.getElementById('tableBody');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const pageInfo = document.getElementById('pageInfo');

            let timer = null,
                lastAbort = null;

            // state sort awal (fallback dari server)
            const sortState = {
                by: {!! isset($sortBy) ? json_encode($sortBy) : '"fsalesmanid"' !!},
                dir: {!! isset($sortDir) ? json_encode($sortDir) : '"desc"' !!}
            };

            // ✅ SET status awal dari server (selalu 'active' saat refresh)
            if (statusFilter) {
                statusFilter.value = {!! json_encode($status ?? 'active') !!};
            }

            function isDefaultState(curPage, curSortBy, curSortDir, curStatus, curSearch) {
                return (
                    (curSearch ?? '') === '' &&
                    (curStatus ?? 'active') === 'active' &&
                    Number(curPage ?? 1) === 1 &&
                    (curSortBy ?? 'fsalesmanid') === 'fsalesmanid' &&
                    (curSortDir ?? 'desc') === 'desc'
                );
            }

            // modal hapus (tetap)
            window.openDeleteModal = function(url) {
                window.dispatchEvent(new CustomEvent('open-delete', {
                    detail: url
                }));
            };

            // permission awal
            let perms = {
                can_edit: {!! json_encode($canEdit) !!},
                can_delete: {!! json_encode($canDelete) !!}
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
                return `
                <tr class="hover:bg-gray-50">
                    <td class="border px-2 py-1">${item.fsalesmancode ?? ''}</td>
                    <td class="border px-2 py-1">${item.fsalesmanname ?? ''}</td>
                    <td class="border px-2 py-1">${item.status_label ?? ''}</td>
                    ${(perms.can_edit || perms.can_delete) 
                        ? `<td class="border px-2 py-1">${actions}</td>` 
                        : ''}
                </tr>
            `;
            }

            function applySortIcons() {
                ['fsalesmancode', 'fsalesmanname'].forEach(col => {
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

                // table body render
                if (json.data.length === 0) {
                    const colCount = document.querySelector('thead tr').children.length;
                    tbody.innerHTML =
                        `<tr><td colspan="${colCount}" class="text-center py-4">Tidak ada data.</td></tr>`;
                } else {
                    tbody.innerHTML = json.data.map(rowHtml).join('');
                }

                // pagination
                prevBtn.dataset.page = json.links.prev || '';
                nextBtn.dataset.page = json.links.next || '';
                prevBtn.disabled = !json.links.prev;
                nextBtn.disabled = !json.links.next;
                prevBtn.classList.toggle('opacity-50', !json.links.prev);
                nextBtn.classList.toggle('opacity-50', !json.links.next);
                pageInfo.textContent = `Page ${json.links.current_page} of ${json.links.last_page}`;

                // sort state from server
                if (json.sort && json.sort.by) {
                    sortState.by = json.sort.by;
                    sortState.dir = json.sort.dir || 'desc';
                }
                applySortIcons();

                // ✅ UPDATE dropdown status dari response server
                if (json.filters && typeof json.filters.status !== 'undefined' && statusFilter) {
                    statusFilter.value = json.filters.status; // boleh kosong ("")
                }

                // ✅ UPDATE URL BAR: Jika state default, gunakan URL bersih
                const currentPage = json.links.current_page;
                const currentStatus = json.filters?.status || 'active';
                const currentSearch = input?.value?.trim() || '';

                // Cek apakah dalam state default
                const isDefault = isDefaultState(currentPage, sortState.by, sortState.dir, currentStatus,
                    currentSearch);

                if (isDefault) {
                    // State default: PAKSA URL bersih tanpa query string
                    const cleanUrl = window.location.origin + window.location.pathname;
                    history.replaceState({}, '', cleanUrl);
                } else {
                    // Bukan state default: tambahkan HANYA parameter yang non-default
                    const qs = new URLSearchParams();

                    if (currentSearch !== '') qs.set('search', currentSearch);
                    if (currentStatus !== 'active') qs.set('status', currentStatus);
                    if (currentPage > 1) qs.set('page', currentPage);
                    if (sortState.by !== 'fsalesmanid') qs.set('sort_by', sortState.by);
                    if (sortState.dir !== 'desc') qs.set('sort_dir', sortState.dir);

                    const queryString = qs.toString();
                    const newUrl = queryString ? `${window.location.pathname}?${queryString}` : window.location
                        .pathname;
                    history.replaceState({}, '', newUrl);
                }
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
                const base = baseUrl ? new URL(baseUrl, location.origin) :
                    new URL(form.getAttribute('action'), location.origin);

                base.searchParams.set('search', input?.value || '');
                if (filter) base.searchParams.set('filter_by', filter.value || 'all');

                // ← keep exactly what user chose: 'active' | 'nonactive' | 'all'
                if (statusFilter) base.searchParams.set('status', statusFilter.value ?? 'active');

                base.searchParams.set('sort_by', sortState.by);
                base.searchParams.set('sort_dir', sortState.dir);
                if (!baseUrl) base.searchParams.delete('page');
                return base.toString();
            }

            // live search (debounce)
            input.addEventListener('input', () => {
                clearTimeout(timer);
                timer = setTimeout(() => fetchTable(buildUrl()), 300);
            });

            // ✅ Saat status filter berubah, fetch ulang
            statusFilter?.addEventListener('change', () => {
                fetchTable(buildUrl());
            });

            // cegah Enter submit
            input.addEventListener('keydown', e => {
                if (e.key === 'Enter') e.preventDefault();
            });

            // klik header untuk sort (tanpa refresh)
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

            // ✅ BERSIHKAN URL saat page load jika dalam state default
            (function cleanUrlOnLoad() {
                const urlParams = new URLSearchParams(window.location.search);
                const currentSearch = input?.value?.trim() || '';
                const currentStatus = statusFilter?.value || 'active';
                const currentPage = urlParams.get('page') || '1';
                const currentSortBy = urlParams.get('sort_by') || 'fsalesmanid';
                const currentSortDir = urlParams.get('sort_dir') || 'desc';

                if (isDefaultState(currentPage, currentSortBy, currentSortDir, currentStatus, currentSearch)) {
                    const cleanUrl = window.location.origin + window.location.pathname;
                    history.replaceState({}, '', cleanUrl);
                }
            })();
        })();
    </script>
@endpush
