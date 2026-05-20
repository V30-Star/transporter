@extends('layouts.app')

@section('title', $pageTitle)

@section('content')
    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded shadow p-6 md:p-8">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold text-gray-800">{{ $pageTitle }}</h1>
                <p class="mt-2 text-sm text-gray-600">
                    {{ 'Isi periode dalam format YYYYMM. Semua transaksi dengan tanggal sebelum periode ini tidak bisa create/edit/delete sesuai aturan posting periode.' }}
                </p>
            </div>

            <form action="{{ route('editperiode.update') }}" method="POST" class="space-y-5">
                @csrf
                @method('PATCH')

                <div>
                    <label for="fyrmth" class="block text-sm font-medium text-gray-700 mb-2">{{ 'Periode' }}</label>
                    <input type="text" id="fyrmth" name="fyrmth" value="{{ old('fyrmth', $fyrmth) }}"
                        maxlength="6"
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 @error('fyrmth') border-red-500 @enderror"
                        placeholder="202601">
                    @error('fyrmth')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                        class="inline-flex items-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                        {{ 'Simpan' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
