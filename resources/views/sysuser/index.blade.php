@extends('layouts.app')
@section('title', 'Master Wewenang User')

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

        {{-- Search --}}
        <form id="searchForm" method="GET" action="{{ route('sysuser.index') }}"
            class="flex flex-wrap justify-between items-center mb-4 gap-2">
            <div class="flex items-center space-x-2 w-full">
                <label class="font-semibold">Search:</label>
                <input id="searchInput" type="text" name="search" value="{{ $search }}"
                    class="border rounded px-2 py-1 w-1/4" placeholder="Cari...">
                <button type="submit" class="hidden">Cari</button>
            </div>
        </form>
        @php
            $canCreate = in_array('createSysuser', explode(',', session('user_restricted_permissions', '')));
            $canEdit = in_array('updateSysuser', explode(',', session('user_restricted_permissions', '')));
            $canDelete = in_array('deleteSysuser', explode(',', session('user_restricted_permissions', '')));
            $canRoleAccess = in_array('roleaccess', explode(',', session('user_restricted_permissions', '')));
            $showActionsColumn = $canEdit || $canDelete || $canRoleAccess;
        @endphp

        <!-- Table -->
        <table class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-1">User Id</th>
                    <th class="border px-2 py-1">Nama User</th>
                    <th class="border px-2 py-1">Waktu</th>
                    <th class="border px-2 py-1">Fuserid</th>
                    <th class="border px-2 py-1">Cabang</th>
                    @if ($showActionsColumn)
                        <th class="border px-2 py-1">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody id="tableBody">
                @forelse ($sysusers as $sysuser)
                    <tr class="hover:bg-gray-50">
                        <td class="border px-2 py-1">{{ $sysuser->fsysuserid }}</td>
                        <td class="border px-2 py-1">{{ $sysuser->fname }}</td>
                        <td class="border px-2 py-1">{{ $sysuser->created_at }}</td>
                        <td class="border px-2 py-1">{{ $sysuser->fuserid ?? 'N/A' }}</td>
                        <td class="border px-2 py-1">{{ $sysuser->fcabang }}</td>

                        @if ($showActionsColumn)
                            <td class="border px-2 py-1">
                                @if ($canEdit)
                                    <a href="{{ route('sysuser.edit', $sysuser->fuid) }}">
                                        <button
                                            class="inline-flex items-center bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                                            <x-heroicon-o-pencil-square class="w-4 h-4 mr-1" />
                                            Edit
                                        </button>
                                    </a>
                                @endif

                                @if ($canDelete)
                                    <button @click="openDelete('{{ route('sysuser.destroy', $sysuser->fuid) }}')"
                                        class="inline-flex items-center bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                                        <x-heroicon-o-trash class="w-4 h-4 mr-1" />
                                        Hapus
                                    </button>
                                @endif

                                @if ($canRoleAccess)
                                    <a href="{{ route('roleaccess.index', $sysuser->fuid) }}">
                                        <button
                                            class="inline-flex items-center bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                                            <x-heroicon-o-key class="w-4 h-4 mr-1" />
                                            Set Menu
                                        </button>
                                    </a>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{--  Modal Delete  --}}
        <div x-show="showDeleteModal" x-cloak
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div @click.away="closeDelete()" class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Konfirmasi Hapus</h3>
                <p class="mb-6">Apakah Anda yakin ingin menghapus data ini?</p>

                <div class="flex justify-end space-x-2">
                    <button @click="closeDelete()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">
                        Batal
                    </button>

                    <form :action="deleteUrl" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Bottom Actions -->
        <div id="pagination" class="mt-4 flex justify-between items-center">
            <div class="space-x-2">
                @if ($canCreate)
                    <a href="{{ route('sysuser.create') }}"
                        class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        <x-heroicon-o-plus class="w-4 h-4 mr-1" />
                        Baru
                    </a>
                @endif
            </div>

            <div class="flex items-center space-x-2">
                <button id="prevBtn"
                    class="px-3 py-1 rounded border hover:bg-gray-100 {{ $sysusers->onFirstPage() ? 'opacity-50' : '' }}"
                    {{ $sysusers->onFirstPage() ? 'disabled' : '' }}
                    data-page="{{ $sysusers->previousPageUrl() ?? '' }}">&larr;</button>

                <span id="pageInfo" class="text-sm">
                    Page {{ $sysusers->currentPage() }} of {{ $sysusers->lastPage() }}
                </span>

                <button id="nextBtn"
                    class="px-3 py-1 rounded border hover:bg-gray-100 {{ $sysusers->hasMorePages() ? '' : 'opacity-50' }}"
                    {{ $sysusers->hasMorePages() ? '' : 'disabled' }}
                    data-page="{{ $sysusers->nextPageUrl() ?? '' }}">&rarr;</button>
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

            // permission awal dari server (dipakai saat render AJAX)
            let perms = {
                can_edit: {!! json_encode($canEdit) !!},
                can_delete: {!! json_encode($canDelete) !!},
                can_role_access: {!! json_encode($canRoleAccess) !!}
            };

            // helper global untuk buka modal hapus dari HTML hasil AJAX
            window.openDeleteModal = function(url) {
                window.dispatchEvent(new CustomEvent('open-delete', {
                    detail: url
                }));
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
                if (perms.can_role_access) {
                    html += `<a href="${item.can_role_access}"
                    class="inline-flex items-center bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 ml-2">
                    Set Menu
                    </a>`;
                }
                return html;
            }

            function rowHtml(item) {
                const actions = aksiButtons(item);
                return `
   <tr class="hover:bg-gray-50">
     <td class="border px-2 py-1">${item.fsysuserid ?? ''}</td>
     <td class="border px-2 py-1">${item.fname ?? ''}</td>
     <td class="border px-2 py-1">${item.created_at ?? ''}</td>
     <td class="border px-2 py-1">${item.fuserid ?? 'N/A'}</td>
     <td class="border px-2 py-1">${item.fcabang ?? ''}</td>
     ${ (perms.can_edit || perms.can_delete || perms.can_role_access) ? `<td class="border px-2 py-1">${actions}</td>` : '' }
   </tr>
 `;
            }

            function render(json) {
                if (!json || !json.data) return;

                // update perms jika server kirim (antisipasi perubahan session)
                if (json.perms) perms = json.perms;

                if (json.data.length === 0) {
                    const colCount = document.querySelector('thead tr').children.length;
                    tbody.innerHTML =
                        `<tr><td colspan="${colCount}" class="text-center py-4">Tidak ada data.</td></tr>`;
                } else {
                    tbody.innerHTML = json.data.map(rowHtml).join('');
                }

                // update pagination state
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
            // cegah submit via Enter
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
