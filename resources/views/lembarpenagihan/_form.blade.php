@php
    $isReadOnly = in_array($action, ['view', 'delete'], true);
    $isDelete = $action === 'delete';
    $formAction = $action === 'create' ? route('lembarpenagihan.store') : route('lembarpenagihan.update', $header->fstockmtid);
    $detailRows = $details->map(fn($d) => [
        'frefcode' => trim((string) $d->frefcode),
        'frefsono' => trim((string) $d->frefsono),
        'fsodate' => $d->fsodate ? \Carbon\Carbon::parse($d->fsodate)->format('Y-m-d') : '',
        'famountbil' => (float) $d->famountbil,
        'fongkos' => (float) $d->fongkos,
        'famount' => (float) $d->famount,
    ])->values();
@endphp

@extends('layouts.app')

@section('title', $title)

@section('content')
    <form method="POST" action="{{ $isDelete ? route('lembarpenagihan.destroy', $header->fstockmtid) : $formAction }}" class="bg-white rounded shadow p-4"
        x-data="tagihanForm({{ Js::from($detailRows) }})">
        @csrf
        @if ($action === 'edit') @method('PATCH') @endif
        @if ($isDelete) @method('DELETE') @endif

        <div class="grid grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium mb-1">No. Tagihan</label>
                <input type="text" name="ftagihanno" value="{{ old('ftagihanno', $header->ftagihanno ?? $nextNo) }}" readonly class="w-full border rounded px-3 py-2 bg-gray-100">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Customer</label>
                <select name="fcustno" class="w-full border rounded px-3 py-2" {{ $isReadOnly ? 'disabled' : '' }}>
                    <option value="">Pilih Customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->fcustomercode }}" {{ old('fcustno', $header->fcustno ?? '') === $customer->fcustomercode ? 'selected' : '' }}>
                            {{ $customer->fcustomercode }} - {{ $customer->fcustomername }}
                        </option>
                    @endforeach
                </select>
                @if ($isReadOnly)<input type="hidden" name="fcustno" value="{{ $header->fcustno }}">@endif
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Tanggal</label>
                <input type="date" name="fstockmtdate" value="{{ old('fstockmtdate', isset($header) ? \Carbon\Carbon::parse($header->fstockmtdate)->format('Y-m-d') : date('Y-m-d')) }}" class="w-full border rounded px-3 py-2" {{ $isReadOnly ? 'readonly' : '' }}>
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Keterangan</label>
            <textarea name="fnote" rows="2" class="w-full border rounded px-3 py-2" {{ $isReadOnly ? 'readonly' : '' }}>{{ old('fnote', $header->fnote ?? '') }}</textarea>
        </div>

        @if (!$isReadOnly)
            <div class="mb-3 flex gap-2">
                <select id="invoicePicker" class="flex-1 border rounded px-3 py-2">
                    <option value="">Pilih Faktur</option>
                    @foreach ($invoices as $invoice)
                        <option value="{{ $invoice->fsono }}" data-code="INV" data-date="{{ $invoice->fsodate ? \Carbon\Carbon::parse($invoice->fsodate)->format('Y-m-d') : '' }}" data-bil="{{ $invoice->famountbil }}" data-ongkos="{{ $invoice->fongkos }}" data-amount="{{ $invoice->famount }}">
                            {{ $invoice->fsono }} - {{ number_format((float) $invoice->famount, 2, ',', '.') }}
                        </option>
                    @endforeach
                </select>
                <button type="button" @click="addFromSelect('invoicePicker')" class="px-4 py-2 bg-blue-600 text-white rounded">Add Faktur</button>
                <select id="returPicker" class="flex-1 border rounded px-3 py-2">
                    <option value="">Pilih Retur</option>
                    @foreach ($returs as $retur)
                        <option value="{{ $retur->fsono }}" data-code="REJ" data-date="{{ $retur->fsodate ? \Carbon\Carbon::parse($retur->fsodate)->format('Y-m-d') : '' }}" data-bil="{{ $retur->famountbil }}" data-ongkos="0" data-amount="{{ $retur->famount }}">
                            {{ $retur->fsono }} - {{ number_format((float) $retur->famount, 2, ',', '.') }}
                        </option>
                    @endforeach
                </select>
                <button type="button" @click="addFromSelect('returPicker')" class="px-4 py-2 bg-amber-600 text-white rounded">Add Retur</button>
            </div>
        @endif

        <div class="overflow-auto border rounded mb-4">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border px-2 py-1">No.</th>
                        <th class="border px-2 py-1">No.Nota</th>
                        <th class="border px-2 py-1">Tanggal Nota</th>
                        <th class="border px-2 py-1 text-right">Nilai Nota</th>
                        <th class="border px-2 py-1 text-right">Ongkos Kirim</th>
                        <th class="border px-2 py-1 text-right">Sisa Piutang</th>
                        @if (!$isReadOnly)<th class="border px-2 py-1">Aksi</th>@endif
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(row, index) in rows" :key="row.frefsono + index">
                        <tr>
                            <td class="border px-2 py-1" x-text="index + 1"></td>
                            <td class="border px-2 py-1"><span x-text="row.frefsono"></span><input type="hidden" :name="`frefsono[${index}]`" :value="row.frefsono"><input type="hidden" :name="`frefcode[${index}]`" :value="row.frefcode"></td>
                            <td class="border px-2 py-1" x-text="row.fsodate"></td>
                            <td class="border px-2 py-1 text-right" x-text="money(row.famountbil)"></td>
                            <td class="border px-2 py-1 text-right" x-text="money(row.fongkos)"></td>
                            <td class="border px-2 py-1 text-right"><span x-text="money(row.famount)"></span><input type="hidden" :name="`famount[${index}]`" :value="row.famount"></td>
                            @if (!$isReadOnly)<td class="border px-2 py-1"><button type="button" @click="rows.splice(index, 1)" class="px-2 py-1 bg-red-600 text-white rounded">Hapus</button></td>@endif
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="flex justify-end mb-4">
            <div class="border rounded p-3 w-72 bg-gray-50">
                <div class="flex justify-between font-semibold">
                    <span>Total Tagihan:</span>
                    <span x-text="money(total())"></span>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('lembarpenagihan.index') }}" class="px-4 py-2 bg-gray-100 rounded">Kembali</a>
            @if (!$isReadOnly)<button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Simpan</button>@endif
            @if ($isDelete)<button type="submit" class="px-4 py-2 bg-red-600 text-white rounded">Hapus</button>@endif
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        function tagihanForm(initialRows) {
            return {
                rows: initialRows || [],
                addFromSelect(id) {
                    const select = document.getElementById(id);
                    const option = select.options[select.selectedIndex];
                    if (!option || !option.value || this.rows.some(row => row.frefsono === option.value)) return;
                    this.rows.push({
                        frefcode: option.dataset.code || 'INV',
                        frefsono: option.value,
                        fsodate: option.dataset.date || '',
                        famountbil: Number(option.dataset.bil || 0),
                        fongkos: Number(option.dataset.ongkos || 0),
                        famount: Number(option.dataset.amount || 0),
                    });
                    select.value = '';
                },
                total() { return this.rows.reduce((sum, row) => sum + Number(row.famount || 0), 0); },
                money(value) { return Number(value || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            };
        }
    </script>
@endpush
