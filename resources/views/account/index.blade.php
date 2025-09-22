@extends('layouts.app')

@section('title', 'Master Account')

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
    }" class="bg-white rounded shadow p-4">

        {{-- Search (Live) --}}
        <form id="searchForm" method="GET" action="{{ route('account.index') }}"
            class="flex flex-wrap justify-between items-center mb-4 gap-2">
            <div class="flex items-center space-x-2 w-full">
                <label class="font-semibold">Search:</label>
                <input id="searchInput" type="text" name="search" value="{{ $search }}"
                    class="border rounded px-2 py-1 w-1/4" placeholder="Cari...">
                <button type="submit" class="hidden">Cari</button>
            </div>
        </form>

        @php
            $canCreate = in_array('createAccount', explode(',', session('user_restricted_permissions', '')));
            $canEdit = in_array('updateAccount', explode(',', session('user_restricted_permissions', '')));
            $canDelete = in_array('deleteAccount', explode(',', session('user_restricted_permissions', '')));
            $showActionsColumn = $canEdit || $canDelete;
        @endphp

        {{-- Table --}}
        <table class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-1 cursor-pointer sortCol" data-sort-by="faccount">
                        <div class="flex items-center gap-1">
                            <span>Account #</span>
                            <span id="icon-faccount" class="text-lg font-semibold text-green-600">⇅</span>
                        </div>
                    </th>
                    <th class="border px-2 py-1 cursor-pointer sortCol" data-sort-by="faccname">
                        <div class="flex items-center gap-1">
                            <span>Nama Account</span>
                            <span id="icon-faccname" class="text-lg font-semibold text-green-600">⇅</span>
                        </div>
                    </th>
                    <th class="border px-2 py-1">Type</th>
                    <th class="border px-2 py-1">Saldo Normal</th>
                    @if ($showActionsColumn)
                        <th class="border px-2 py-1">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody id="tableBody">
                @forelse($accounts as $account)
                    <tr class="hover:bg-gray-50">
                        <td class="border px-2 py-1">{{ $account->faccount }}</td>
                        <td class="border px-2 py-1">{{ $account->faccname }}</td>
                        <td class="border px-2 py-1">{{ $account->fend == 1 ? 'Detil' : 'Header' }}</td>
                        <td class="border px-2 py-1"> {{ $account->fnormal == 'D' ? 'D' : '' }}</td>

                        @if ($showActionsColumn)
                            <td class="border px-2 py-1 space-x-2">
                                @if ($canEdit)
                                    <a href="{{ route('account.edit', $account->faccid) }}">
                                        <button
                                            class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                                            <x-heroicon-o-pencil-square class="w-4 h-4 mr-1" /> Edit
                                        </button>
                                    </a>
                                @endif

                                @if ($canDelete)
                                    <button @click="openDelete('{{ route('account.destroy', $account->faccid) }}')"
                                        class="inline-flex items-center bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                                        <x-heroicon-o-trash class="w-4 h-4 mr-1" /> Hapus
                                    </button>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $showActionsColumn ? 5 : 4 }}" class="text-center py-4">Tidak ada data.</td>
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
                @if ($canCreate)
                    <a href="{{ route('account.create') }}"
                        class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        <x-heroicon-o-plus class="w-4 h-4 mr-1" /> Baru
                    </a>
                @endif
            </div>

            <div class="flex items-center space-x-2">
                <button id="prevBtn"
                    class="px-3 py-1 rounded border hover:bg-gray-100 {{ $accounts->onFirstPage() ? 'opacity-50' : '' }}"
                    {{ $accounts->onFirstPage() ? 'disabled' : '' }}
                    data-page="{{ $accounts->previousPageUrl() ?? '' }}">&larr;</button>

                <span id="pageInfo" class="text-sm">
                    Page {{ $accounts->currentPage() }} of {{ $accounts->lastPage() }}
                </span>

                <button id="nextBtn"
                    class="px-3 py-1 rounded border hover:bg-gray-100 {{ $accounts->hasMorePages() ? '' : 'opacity-50' }}"
                    {{ $accounts->hasMorePages() ? '' : 'disabled' }}
                    data-page="{{ $accounts->nextPageUrl() ?? '' }}">&rarr;</button>
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

            // permissions awal utk render AJAX
            let perms = {
                can_edit: {!! json_encode($canEdit) !!},
                can_delete: {!! json_encode($canDelete) !!}
            };

            // state sort awal dari server
            const sortState = {
                by: {!! isset($sortBy) ? json_encode($sortBy) : '"faccount"' !!},
                dir: {!! isset($sortDir) ? json_encode($sortDir) : '"asc"' !!}
            };

            // helper untuk buka modal delete dari row hasil AJAX
            window.openDeleteModal = function(url) {
                document.querySelector('[x-data]')?.__x?.$data?.openDelete?.(url);
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
                const typeLabel = (parseInt(item.fend, 10) === 1) ? 'Detil' : 'Header';
                const normLabel = (parseInt(item.fnormal, 10) === 1) ? 'Debet' : 'Kredit';
                const actions = aksiButtons(item);
                const showAksi = (perms.can_edit || perms.can_delete);

                return `
        <tr class="hover:bg-gray-50">
            <td class="border px-2 py-1">${item.faccount ?? ''}</td>
            <td class="border px-2 py-1">${item.faccname ?? ''}</td>
            <td class="border px-2 py-1">${typeLabel}</td>
            <td class="border px-2 py-1">${normLabel}</td>
            ${ showAksi ? `<td class="border px-2 py-1">${actions}</td>` : '' }
        </tr>`;
            }

            function applySortIcons() {
                ['faccount', 'faccname'].forEach(col => {
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

                if (!json.data.length) {
                    const colCount = document.querySelector('thead tr').children.length;
                    tbody.innerHTML =
                        `<tr><td colspan="${colCount}" class="text-center py-4">Tidak ada data.</td></tr>`;
                } else {
                    tbody.innerHTML = json.data.map(rowHtml).join('');
                }

                prevBtn.dataset.page = json.links.prev || '';
                nextBtn.dataset.page = json.links.next || '';
                prevBtn.disabled = !json.links.prev;
                nextBtn.disabled = !json.links.next;
                prevBtn.classList.toggle('opacity-50', !json.links.prev);
                nextBtn.classList.toggle('opacity-50', !json.links.next);
                pageInfo.textContent = `Page ${json.links.current_page} of ${json.links.last_page}`;

                if (json.sort && json.sort.by) {
                    sortState.by = json.sort.by;
                    sortState.dir = json.sort.dir || 'asc';
                }
                applySortIcons();

                // (opsional) sync URL tanpa reload
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
                    .catch(err => {
                        if (err.name !== 'AbortError') console.error(err);
                    });
            }

            function buildUrl(baseUrl = null) {
                const base = baseUrl ? new URL(baseUrl, window.location.origin) :
                    new URL(form.getAttribute('action'), window.location.origin);
                base.searchParams.set('search', input?.value || '');
                base.searchParams.set('sort_by', sortState.by);
                base.searchParams.set('sort_dir', sortState.dir);
                if (!baseUrl) base.searchParams.delete('page'); // reset page saat bukan pagination
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

            applySortIcons();
        })();
    </script>
@endpush
