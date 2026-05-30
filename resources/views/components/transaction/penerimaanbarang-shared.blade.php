@php
    $section = $section ?? '';
@endphp

@if ($section === 'browser_globals_rich')
window.CURRENCY_MAP = window.CURRENCY_MAP || {};

window.PRODUCT_MAP = {
    @foreach ($products as $p)
        @php
            $smallUnit = trim((string) ($p->fsatuankecil ?? ''));
            $largeUnit = trim((string) ($p->fsatuanbesar ?? ''));
            $largeUnit2 = trim((string) ($p->fsatuanbesar2 ?? ''));
            $defaultKey = trim((string) ($p->fsatuandefault ?? ''));
            $resolvedDefaultUnit = match ($defaultKey) {
                '1' => $smallUnit,
                '2' => $largeUnit,
                '3' => $largeUnit2,
                default => in_array(strtoupper($defaultKey), [
                    strtoupper($smallUnit),
                    strtoupper($largeUnit),
                    strtoupper($largeUnit2),
                ], true)
                    ? $defaultKey
                    : ($smallUnit ?: $largeUnit ?: $largeUnit2),
            };
            $orderedUnits = array_values(array_unique(array_filter([
                $resolvedDefaultUnit,
                $smallUnit,
                $largeUnit,
                $largeUnit2,
            ])));
        @endphp
        "{{ $p->fprdcode }}": {
            id: @json($p->fprdid),
            name: @json($p->fprdname),
            default_unit: @json($resolvedDefaultUnit),
            units: @json($orderedUnits),
            stock: @json($p->fminstock ?? 0),
            unit_ratios: {
                satuankecil: 1,
                satuanbesar: @json((float) ($p->fqtykecil ?? 1)),
                satuanbesar2: @json((float) ($p->fqtykecil2 ?? 1)),
            },
        },
    @endforeach
};

window.cryptoRandom = window.cryptoRandom || function() {
    try {
        if (window.crypto?.getRandomValues) {
            const arr = new Uint32Array(1);
            window.crypto.getRandomValues(arr);
            return 'r' + arr[0].toString(16);
        }
    } catch (e) {}

    return 'r' + (Date.now().toString(16) + Math.random().toString(16).slice(2));
};
@elseif ($section === 'browser_globals_basic')
window.PRODUCT_MAP = {
    @foreach ($products as $p)
        @php
            $smallUnit = trim((string) ($p->fsatuankecil ?? ''));
            $largeUnit = trim((string) ($p->fsatuanbesar ?? ''));
            $largeUnit2 = trim((string) ($p->fsatuanbesar2 ?? ''));
            $defaultKey = trim((string) ($p->fsatuandefault ?? ''));
            $resolvedDefaultUnit = match ($defaultKey) {
                '1' => $smallUnit,
                '2' => $largeUnit,
                '3' => $largeUnit2,
                default => in_array(strtoupper($defaultKey), [
                    strtoupper($smallUnit),
                    strtoupper($largeUnit),
                    strtoupper($largeUnit2),
                ], true)
                    ? $defaultKey
                    : ($smallUnit ?: $largeUnit ?: $largeUnit2),
            };
            $orderedUnits = array_values(array_unique(array_filter([
                $resolvedDefaultUnit,
                $smallUnit,
                $largeUnit,
                $largeUnit2,
            ])));
        @endphp
        "{{ $p->fprdcode }}": {
            name: @json($p->fprdname),
            default_unit: @json($resolvedDefaultUnit),
            units: @json($orderedUnits),
            stock: @json($p->fminstock ?? 0)
        },
    @endforeach
};

window.cryptoRandom = window.cryptoRandom || function() {
    try {
        if (window.crypto?.getRandomValues) {
            const arr = new Uint32Array(1);
            window.crypto.getRandomValues(arr);
            return 'r' + arr[0].toString(16);
        }
    } catch (e) {}

    return 'r' + (Date.now().toString(16) + Math.random().toString(16).slice(2));
};
@elseif ($section === 'draft_unit_dom_helpers')
function getDraftUnitSelect() {
    return document.getElementById('draftUnitSelect');
}

function populateDraftUnitSelect(units) {
    const sel = getDraftUnitSelect();
    if (!sel) return;
    sel.innerHTML = '';
    units.forEach(u => {
        const opt = document.createElement('option');
        opt.value = u;
        opt.textContent = u;
        sel.appendChild(opt);
    });
}

function clearDraftUnitSelect() {
    const sel = getDraftUnitSelect();
    if (sel) sel.innerHTML = '';
}
@elseif ($section === 'format_date_helper')
function formatDate(s) {
    if (!s || s === 'No Date') return '-';
    const d = new Date(s);
    if (isNaN(d.getTime())) return '-';
    const pad = n => n.toString().padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
}
@elseif ($section === 'desc_store')
document.addEventListener('alpine:init', () => {
    let existingStore;
    try {
        existingStore = Alpine.store('prh');
    } catch (e) {
        existingStore = undefined;
    }

    if (existingStore === undefined) {
        Alpine.store('prh', {
            descPreview: {
                uid: null,
                index: null,
                label: '',
                text: ''
            },
            descList: []
        });
    }
});
@endif
