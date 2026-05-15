@php
    $section = $section ?? '';
@endphp

@if ($section === 'browser_globals_rich')
window.CURRENCY_MAP = window.CURRENCY_MAP || {};

window.PRODUCT_MAP = {
    @foreach ($products as $p)
        "{{ $p->fprdcode }}": {
            id: @json($p->fprdid),
            name: @json($p->fprdname),
            units: @json(array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2]))),
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
        "{{ $p->fprdcode }}": {
            name: @json($p->fprdname),
            units: @json(array_values(array_filter([$p->fsatuankecil, $p->fsatuanbesar, $p->fsatuanbesar2]))),
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
    return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
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
