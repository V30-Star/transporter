<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard')</title>

    <style>
        [x-cloak] {
            display: none !important
        }
    </style>
    @if (session('error') || $errors->any())
        <style>
            .alert.alert-danger[role="alert"],
            .border-red-200.bg-red-50[role="alert"] {
                display: none !important;
            }
        </style>
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Lato&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Lato', sans-serif;
        }
    </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @stack('styles')
</head>

<body class="bg-gray-100" style="font-family: 'Lato', sans-serif;">
    <div x-data="{
        openSidebar: true,
        updateSidebarState() {
            if (window.innerWidth <= 1024) {
                this.openSidebar = false;
            } else {
                this.openSidebar = JSON.parse(localStorage.getItem('desktopSidebarOpen') ?? 'true');
            }
        }
    }" x-init="updateSidebarState();
    
    if (sessionStorage.getItem('collapseSidebarOnce') === '1') {
        openSidebar = false;
        sessionStorage.removeItem('collapseSidebarOnce');
        if (window.innerWidth > 1024) {
            localStorage.setItem('desktopSidebarOpen', false);
        }
    }
    
    $watch('openSidebar', value => {
        if (window.innerWidth > 1024) {
            localStorage.setItem('desktopSidebarOpen', value);
        }
    });
    
    window.addEventListener('pageshow', () => updateSidebarState());" @resize.window="updateSidebarState()"
        class="flex min-h-screen">

        <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>

        <div :class="openSidebar ? 'w-64' : 'w-16'"
            class="flex-shrink-0 bg-black text-white shadow-md overflow-y-auto transition-all duration-300">
            <x-sidebar />
        </div>

        <div class="flex-1 min-w-0 overflow-auto flex flex-col">
            <header class="bg-white shadow-sm p-4 flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">@yield('title', 'Dashboard')</h2>

                <div class="flex flex-col items-end gap-1 ml-auto">
                    @auth
                        <div class="text-sm text-gray-600 font-medium mr-4">
                            <span>{{ Auth::user()->fname }} | {{ Auth::user()->fsysuserid }}</span>
                        </div>
                    @endauth

                    <div x-data="{
                        display: '',
                        update() {
                            const dt = new Date();
                    
                            const months = [
                                'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
                            ];
                    
                            const day = dt.getDate();
                            const month = months[dt.getMonth()];
                            const year = dt.getFullYear();
                            const hour = dt.getHours().toString().padStart(2, '0');
                            const minute = dt.getMinutes().toString().padStart(2, '0');
                            const second = dt.getSeconds().toString().padStart(2, '0');
                    
                            this.display = `${day} ${month} ${year} | ${hour}:${minute}:${second}`;
                        },
                        init() {
                            this.update();
                            setInterval(() => this.update(), 1000);
                        }
                    }" x-init="init()" class="text-sm text-gray-600 font-medium mr-4"
                        x-text="display"></div>
                </div>

                @auth
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center space-x-2 focus:outline-none">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}"
                                class="w-8 h-8 rounded-full" alt="avatar">
                            <span class="text-gray-700 hidden sm:inline">{{ Auth::user()->name }}</span>
                        </button>

                        <div x-show="open" @click.outside="open = false"
                            class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-20" x-cloak>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth
            </header>

            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4"
                    role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <main class="p-6 flex-1">
                @yield('content')
            </main>
        </div>
    </div>
    @php
        $transactionErrorMessages = collect();

        if (session('error')) {
            $transactionErrorMessages->push((string) session('error'));
        }

        if ($errors->any()) {
            $transactionErrorMessages = $transactionErrorMessages->merge($errors->all());
        }

        $transactionErrorMessages = $transactionErrorMessages
            ->map(fn ($message) => trim((string) $message))
            ->filter()
            ->unique()
            ->values();
    @endphp
    @stack('scripts')
    <script>
        (() => {
            if (window.transactionReferenceModalHelper) {
                return;
            }

            window.transactionReferenceModalHelper = {
                dispatchPick(eventName, header, items) {
                    window.dispatchEvent(new CustomEvent(eventName, {
                        detail: {
                            header,
                            items
                        }
                    }));
                },

                openDupModal(state, header, duplicates, uniques) {
                    state.dupCount = duplicates.length;
                    state.dupSample = duplicates.slice(0, 6);
                    state.pendingHeader = header;
                    state.pendingUniques = uniques;
                    state.showDupModal = true;
                },

                closeDupModal(state) {
                    state.showDupModal = false;
                    state.dupCount = 0;
                    state.dupSample = [];
                    state.pendingHeader = null;
                    state.pendingUniques = [];
                },

                confirmAddUniques(state, eventName, options = {}) {
                    const detailBuilder = typeof options.detailBuilder === 'function'
                        ? options.detailBuilder
                        : (header, items) => ({
                            header,
                            items
                        });

                    const detail = detailBuilder(state.pendingHeader, state.pendingUniques);

                    window.dispatchEvent(new CustomEvent(eventName, {
                        detail
                    }));

                    this.closeDupModal(state);

                    if (options.skipCloseModal === true) {
                        return;
                    }

                    if (typeof state.closeModal === 'function') {
                        state.closeModal();
                    }
                }
            };
        })();
    </script>
    @if ($transactionErrorMessages->isNotEmpty())
        <script>
            (() => {
                const messages = @json($transactionErrorMessages);

                function escapeHtml(value) {
                    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#39;'
                    }[char]));
                }

                function inferReason(items) {
                    const text = items.join(' ').toLowerCase();

                    if (text.includes('minimal satu item') || text.includes('data items') || text.includes('detail')) {
                        return 'Detail item transaksi masih belum lengkap atau belum valid.';
                    }

                    if (text.includes('qty') || text.includes('quantity')) {
                        return 'Qty yang diinput belum sesuai batas atau format yang diperbolehkan.';
                    }

                    if (text.includes('supplier') || text.includes('customer') || text.includes('salesman') || text.includes('gudang')) {
                        return 'Data referensi transaksi masih ada yang kosong atau tidak cocok.';
                    }

                    if (text.includes('close') || text.includes('referensi')) {
                        return 'Status transaksi belum bisa diproses karena syarat referensinya belum terpenuhi.';
                    }

                    return 'Masih ada data yang belum valid, jadi sistem menolak proses simpan.';
                }

                document.addEventListener('DOMContentLoaded', () => {
                    document.querySelectorAll('.alert.alert-danger[role="alert"], .border-red-200.bg-red-50[role="alert"]')
                        .forEach((element) => element.remove());

                    const listHtml = messages.map((message) =>
                        `<li style="margin-bottom:8px;">${escapeHtml(message)}</li>`
                    ).join('');

                    Swal.fire({
                        icon: 'error',
                        title: 'Transaksi Belum Bisa Disimpan',
                        html: `
                            <div style="text-align:left; font-size:14px; line-height:1.6;">
                                <p style="margin:0 0 10px 0;"><strong>Alasan:</strong> ${escapeHtml(inferReason(messages))}</p>
                                <p style="margin:0 0 8px 0;">Sistem menemukan masalah berikut:</p>
                                <ul style="margin:0 0 12px 18px; padding:0;">${listHtml}</ul>
                                <p style="margin:0; color:#6b7280;">Silakan perbaiki data di atas, lalu coba simpan kembali.</p>
                            </div>
                        `,
                        confirmButtonText: 'Tutup',
                        confirmButtonColor: '#dc2626',
                        width: 640
                    });
                });
            })();
        </script>
    @endif
    <script>
        (() => {
            const blockedQtyKeys = new Set([',', '.', 'e', 'E', '+', '-']);

            function isWholeQtyInput(element) {
                if (!(element instanceof HTMLInputElement)) {
                    return false;
                }

                if (element.type !== 'number' || element.disabled || element.readOnly) {
                    return false;
                }

                const alpineModel = element.getAttribute('x-model.number') || element.getAttribute('x-model') || '';
                return alpineModel.endsWith('.fqty');
            }

            function bindWholeQtyInput(input) {
                if (!isWholeQtyInput(input) || input.dataset.wholeQtyBound === '1') {
                    return;
                }

                input.dataset.wholeQtyBound = '1';
                input.setAttribute('step', '1');
                input.setAttribute('inputmode', 'numeric');

                input.addEventListener('keydown', (event) => {
                    if (blockedQtyKeys.has(event.key)) {
                        event.preventDefault();
                    }
                }, true);

                input.addEventListener('paste', (event) => {
                    const pastedText = event.clipboardData?.getData('text') ?? '';
                    if (!pastedText) {
                        return;
                    }

                    const digitsOnly = pastedText.replace(/\D/g, '');
                    if (digitsOnly === pastedText) {
                        return;
                    }

                    event.preventDefault();
                    document.execCommand('insertText', false, digitsOnly);
                }, true);

                input.addEventListener('input', () => {
                    if (input.value === '') {
                        return;
                    }

                    const digitsOnly = input.value.replace(/\D/g, '');
                    if (digitsOnly !== input.value) {
                        input.value = digitsOnly;
                    }
                }, true);
            }

            function bindWholeQtyInputs(root = document) {
                root.querySelectorAll('input[type="number"]').forEach(bindWholeQtyInput);
            }

            document.addEventListener('DOMContentLoaded', () => {
                bindWholeQtyInputs();

                const observer = new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        mutation.addedNodes.forEach((node) => {
                            if (!(node instanceof HTMLElement)) {
                                return;
                            }

                            if (node.matches?.('input[type="number"]')) {
                                bindWholeQtyInput(node);
                            }

                            bindWholeQtyInputs(node);
                        });
                    });
                });

                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            });
        })();
    </script>
    <script>
        (() => {
            const shouldRestoreFormState = @json($errors->any() || session()->hasOldInput() || session()->has('error'));
            const formSelector = 'form:not([method="GET"]):not([data-disable-form-persist="true"])';

            const hasSuccessFlash = @json(session()->has('success'));
            const draftFormSelector = 'form[data-form-draft="true"]';
            const draftPrefix = 'transaction-form-draft:';
            const pendingSubmitKey = 'transaction-form-draft:pending-submit-keys';
            const draftTtlMs = 1000 * 60 * 60 * 24 * 7;

            function formStorageKey(form) {
                const customKey = form.dataset.draftKey;
                if (customKey) {
                    return `${draftPrefix}${customKey}`;
                }

                const action = form.getAttribute('action') || window.location.pathname;
                return `${draftPrefix}${window.location.pathname}:${action}`;
            }

            function readPendingKeys() {
                try {
                    const raw = sessionStorage.getItem(pendingSubmitKey);
                    const parsed = raw ? JSON.parse(raw) : [];
                    return Array.isArray(parsed) ? parsed : [];
                } catch (error) {
                    return [];
                }
            }

            function writePendingKeys(keys) {
                sessionStorage.setItem(pendingSubmitKey, JSON.stringify(Array.from(new Set(keys))));
            }

            function addPendingKey(key) {
                const keys = readPendingKeys();
                keys.push(key);
                writePendingKeys(keys);
            }

            function clearSubmittedDrafts() {
                const keys = readPendingKeys();
                keys.forEach((key) => localStorage.removeItem(key));
                sessionStorage.removeItem(pendingSubmitKey);
            }

            function normalizeDraftKey(key) {
                const rawKey = (key || '').toString().trim();
                if (!rawKey) {
                    return '';
                }

                return rawKey.startsWith(draftPrefix) ? rawKey : `${draftPrefix}${rawKey}`;
            }

            function clearDraftKeys(keys) {
                const normalizedKeys = keys
                    .map((key) => normalizeDraftKey(key))
                    .filter(Boolean);

                if (normalizedKeys.length === 0) {
                    return;
                }

                normalizedKeys.forEach((key) => localStorage.removeItem(key));

                const remainingPendingKeys = readPendingKeys().filter((key) => !normalizedKeys.includes(key));
                if (remainingPendingKeys.length > 0) {
                    writePendingKeys(remainingPendingKeys);
                } else {
                    sessionStorage.removeItem(pendingSubmitKey);
                }
            }

            function getAlpineFormState(form) {
                if (Array.isArray(form?._x_dataStack) && form._x_dataStack.length > 0) {
                    return form._x_dataStack[0];
                }

                if (form?.__x?.$data) {
                    return form.__x.$data;
                }

                return null;
            }

            function serializeForm(form) {
                const data = {};

                form.querySelectorAll('input[name], select[name], textarea[name]').forEach((field) => {
                    if (!field.name || field.disabled || field.type === 'file' || field.name === '_token') {
                        return;
                    }

                    if (field.type === 'checkbox') {
                        if (!Array.isArray(data[field.name])) {
                            data[field.name] = [];
                        }

                        if (field.checked) {
                            data[field.name].push(field.value);
                        }
                        return;
                    }

                    if (field.type === 'radio') {
                        if (field.checked) {
                            data[field.name] = field.value;
                        }
                        return;
                    }

                    if (field.tagName === 'SELECT' && field.multiple) {
                        data[field.name] = Array.from(field.selectedOptions).map((option) => option.value);
                        return;
                    }

                    data[field.name] = field.value;
                });

                const customState = {};
                const alpineState = getAlpineFormState(form);
                if (alpineState?.savedItems && Array.isArray(alpineState.savedItems)) {
                    customState.savedItems = JSON.parse(JSON.stringify(alpineState.savedItems));
                }

                const collectEvent = new CustomEvent('form-draft-collect', {
                    bubbles: true,
                    detail: {
                        customState
                    }
                });
                form.dispatchEvent(collectEvent);

                return {
                    values: data,
                    customState,
                    updatedAt: Date.now()
                };
            }

            function syncLinkedSelect(hiddenId, selectId) {
                const hidden = document.getElementById(hiddenId);
                const select = document.getElementById(selectId);
                if (!hidden || !select) {
                    return;
                }

                const value = (hidden.value || '').toString().trim();
                let found = false;

                Array.from(select.options).forEach((option) => {
                    const selected = String(option.value) === value;
                    option.selected = selected;
                    if (selected) {
                        found = true;
                    }
                });

                if (!found && value) {
                    select.add(new Option(value, value, true, true));
                }

                select.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }

            function syncCommonDisplayFields() {
                [
                    ['supplierCodeHidden', 'modal_filter_supplier_id'],
                    ['customerCodeHidden', 'modal_filter_customer_id'],
                    ['warehouseCodeHidden', 'warehouseSelect']
                ].forEach(([hiddenId, selectId]) => syncLinkedSelect(hiddenId, selectId));
            }

            function restoreField(field, value) {
                if (field.type === 'checkbox') {
                    const values = Array.isArray(value) ? value.map(String) : [String(value)];
                    field.checked = values.includes(String(field.value));
                    return;
                }

                if (field.type === 'radio') {
                    field.checked = String(value) === String(field.value);
                    return;
                }

                if (field.tagName === 'SELECT' && field.multiple) {
                    const values = Array.isArray(value) ? value.map(String) : [];
                    Array.from(field.options).forEach((option) => {
                        option.selected = values.includes(String(option.value));
                    });
                    return;
                }

                field.value = value ?? '';
            }

            function triggerFieldEvents(field) {
                field.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
                field.dispatchEvent(new Event('change', {
                    bubbles: true
                }));

                if (window.jQuery) {
                    window.jQuery(field).trigger('change.select2');
                    window.jQuery(field).trigger('change');
                }
            }

            function restoreForm(form, savedDraft) {
                const data = savedDraft && typeof savedDraft === 'object' ? savedDraft.values : null;
                if (!data || typeof data !== 'object') {
                    return;
                }

                form.querySelectorAll('input[name], select[name], textarea[name]').forEach((field) => {
                    if (!field.name || !(field.name in data) || field.type === 'file' || field.name === '_token') {
                        return;
                    }

                    restoreField(field, data[field.name]);
                    triggerFieldEvents(field);
                });

                syncCommonDisplayFields();

                const alpineState = getAlpineFormState(form);
                const savedItems = savedDraft?.customState?.savedItems;
                if (Array.isArray(savedItems) && savedItems.length > 0) {
                    if (alpineState && typeof alpineState.restoreSavedItems === 'function') {
                        alpineState.restoreSavedItems(savedItems);
                    } else if (alpineState && Array.isArray(alpineState.savedItems)) {
                        alpineState.savedItems = JSON.parse(JSON.stringify(savedItems));
                        if (typeof alpineState.recalcTotals === 'function') {
                            alpineState.recalcTotals();
                        }
                    }
                }

                form.dispatchEvent(new CustomEvent('form-draft-restored', {
                    bubbles: true,
                    detail: {
                        draft: savedDraft,
                        values: data
                    }
                }));
            }

            function isDraftExpired(savedDraft) {
                if (!savedDraft || typeof savedDraft !== 'object') {
                    return true;
                }

                if (!savedDraft.updatedAt) {
                    return false;
                }

                return (Date.now() - savedDraft.updatedAt) > draftTtlMs;
            }

            document.addEventListener('DOMContentLoaded', () => {
                if (hasSuccessFlash) {
                    clearSubmittedDrafts();
                }

                const clearOnLoadKeys = (document.body?.dataset?.clearFormDrafts || '')
                    .split(',')
                    .map((key) => key.trim())
                    .filter(Boolean);

                if (clearOnLoadKeys.length > 0) {
                    clearDraftKeys(clearOnLoadKeys);
                }

                document.querySelectorAll(draftFormSelector).forEach((form) => {
                    const storageKey = formStorageKey(form);
                    let saveTimer = null;

                    try {
                        const saved = localStorage.getItem(storageKey);
                        if (saved) {
                            const parsed = JSON.parse(saved);
                            if (isDraftExpired(parsed)) {
                                localStorage.removeItem(storageKey);
                            } else {
                                restoreForm(form, parsed);
                            }
                        }
                    } catch (error) {
                        localStorage.removeItem(storageKey);
                    }

                    const persist = () => {
                        window.clearTimeout(saveTimer);
                        saveTimer = window.setTimeout(() => {
                            localStorage.setItem(storageKey, JSON.stringify(serializeForm(form)));
                        }, 150);
                    };

                    form.addEventListener('input', persist);
                    form.addEventListener('change', persist);
                    form.addEventListener('submit', () => {
                        form.dataset.formDraftSubmitted = '1';
                        localStorage.setItem(storageKey, JSON.stringify(serializeForm(form)));
                        addPendingKey(storageKey);
                    });
                });

                window.addEventListener('pagehide', () => {
                    const keysToClear = Array.from(document.querySelectorAll(draftFormSelector))
                        .filter((form) => form.dataset.formDraftSubmitted !== '1')
                        .map((form) => formStorageKey(form));

                    if (keysToClear.length > 0) {
                        clearDraftKeys(keysToClear);
                    }
                });
            });
        })();
    </script>
</body>

</html>
