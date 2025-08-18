@extends('layouts.app')

@section('title', 'Tambah Group Customer')

@section('content')
    <div class="relative min-h-[calc(100vh-64px)]">

        <!-- subtle background decorations -->
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -top-20 -left-20 h-72 w-72 rounded-full bg-blue-50 blur-3xl opacity-60"></div>
            <div class="absolute bottom-10 -right-24 h-80 w-80 rounded-full bg-indigo-50 blur-3xl opacity-60"></div>
            <div
                class="absolute inset-x-0 top-24 mx-auto h-px max-w-6xl bg-gradient-to-r from-transparent via-slate-200 to-transparent">
            </div>
        </div>

        <div class="relative mx-auto max-w-6xl px-4 py-10 lg:py-12">
            <div class="grid gap-6 lg:grid-cols-[1fr,360px]">

                <!-- MAIN CARD (FORM) -->
                <div class="order-2 lg:order-1">
                    <div class="relative overflow-hidden rounded-2xl bg-white/90 ring-1 ring-slate-200 shadow-xl">
                        <!-- Header -->
                        <div class="flex items-start gap-3 border-b border-slate-100 px-6 py-5">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 ring-1 ring-blue-100">
                                <x-heroicon-o-user-group class="h-6 w-6 text-blue-600" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-slate-800">Tambah Group Customer</h2>
                                <p class="text-sm text-slate-500">Lengkapi data di bawah, lalu simpan.</p>
                            </div>
                        </div>

                        <!-- Body -->
                        <form id="form-groupproduct" action="{{ route('groupproduct.store') }}" method="POST"
                            class="px-6 py-6 space-y-5">
                            @csrf

                            <!-- Kode Group -->
                            <div>
                                <label for="kode_group" class="mb-1.5 block text-sm font-medium text-slate-700">
                                    Kode Group <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" id="kode_group" name="kode_group" value="{{ old('kode_group') }}"
                                    placeholder="Mis. GRP001"
                                    class="block w-full rounded-lg border-slate-300 text-slate-800 placeholder-slate-400
                       focus:border-blue-500 focus:ring-4 focus:ring-blue-100" />
                                @error('kode_group')
                                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-slate-500">Gunakan format singkat tanpa spasi (maks. 10
                                    karakter).</p>
                            </div>

                            <!-- Nama Group -->
                            <div>
                                <label for="nama_group" class="mb-1.5 block text-sm font-medium text-slate-700">
                                    Nama Group <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" id="nama_group" name="nama_group" value="{{ old('nama_group') }}"
                                    placeholder="Mis. Pelanggan Grosir"
                                    class="block w-full rounded-lg border-slate-300 text-slate-800 placeholder-slate-400
                       focus:border-blue-500 focus:ring-4 focus:ring-blue-100" />
                                @error('nama_group')
                                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Divider tip -->
                            <div class="pt-1">
                                <div class="h-px w-full bg-gradient-to-r from-transparent via-slate-200 to-transparent">
                                </div>
                                <p class="mt-3 text-xs text-slate-500">Pastikan data sudah benar sebelum menyimpan.</p>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                                <a href="{{ route('groupproduct.index') }}"
                                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300
                        px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                    <x-heroicon-o-arrow-left class="h-4 w-4" /> Kembali
                                </a>
                                <button type="submit"
                                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5
                             text-sm font-semibold text-white shadow-sm hover:bg-blue-700
                             focus:outline-none focus:ring-4 focus:ring-blue-200 active:translate-y-[1px]">
                                    <x-heroicon-o-check class="h-5 w-5" /> Simpan
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- breadcrumb -->
                    <div class="mt-6 flex items-center gap-2 text-sm text-slate-500">
                        <a href="{{ route('groupproduct.index') }}" class="hover:text-slate-700">Group Customer</a>
                        <span>/</span>
                        <span class="font-medium text-slate-700">Tambah</span>
                    </div>
                </div>

                <!-- SIDEBAR -->
                <aside class="order-1 space-y-4 lg:order-2">
                    <!-- card: kenapa pakai group -->
                    <div class="rounded-2xl bg-gradient-to-br from-sky-50 to-indigo-50 ring-1 ring-sky-100 p-5">
                        <div class="mb-3 flex items-center gap-2">
                            <x-heroicon-o-sparkles class="h-5 w-5 text-blue-600" />
                            <h3 class="text-sm font-semibold text-slate-800">Kenapa pakai Group?</h3>
                        </div>
                        <ul class="space-y-2 text-sm text-slate-600">
                            <li class="flex gap-2"><x-heroicon-o-check-badge class="h-5 w-5 shrink-0 text-blue-600" />
                                Diskon & aturan harga per grup.</li>
                            <li class="flex gap-2"><x-heroicon-o-check-badge class="h-5 w-5 shrink-0 text-blue-600" /> Akses
                                & approval lebih mudah.</li>
                            <li class="flex gap-2"><x-heroicon-o-check-badge class="h-5 w-5 shrink-0 text-blue-600" />
                                Laporan penjualan per segmen.</li>
                        </ul>
                    </div>

                    <!-- card: tips cepat -->
                    <div class="rounded-2xl bg-white ring-1 ring-slate-200 p-5 shadow">
                        <div class="mb-3 flex items-center gap-2">
                            <x-heroicon-o-light-bulb class="h-5 w-5 text-amber-500" />
                            <h3 class="text-sm font-semibold text-slate-800">Tips cepat</h3>
                        </div>
                        <ul class="space-y-2 text-sm text-slate-600">
                            <li class="flex gap-2"><x-heroicon-o-hashtag class="h-5 w-5 shrink-0 text-slate-400" /> Gunakan
                                kode konsisten (contoh: <span class="font-mono">GRP001</span>).</li>
                            <li class="flex gap-2"><x-heroicon-o-shield-check class="h-5 w-5 shrink-0 text-slate-400" />
                                Hindari spasi & karakter spesial.</li>
                            <li class="flex gap-2"><x-heroicon-o-queue-list class="h-5 w-5 shrink-0 text-slate-400" /> Nama
                                jelas & deskriptif (mis. “Pelanggan Grosir”).</li>
                        </ul>
                    </div>

                    <!-- card: bantuan -->
                    <div class="rounded-2xl bg-white ring-1 ring-slate-200 p-5 shadow">
                        <div class="mb-3 flex items-center gap-2">
                            <x-heroicon-o-question-mark-circle class="h-5 w-5 text-indigo-600" />
                            <h3 class="text-sm font-semibold text-slate-800">Butuh bantuan?</h3>
                        </div>
                        <p class="text-sm text-slate-600">Kalau terjadi error saat simpan, cek kembali kolom bertanda
                            <span class="text-rose-500 font-medium">*</span> atau hubungi admin.
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <a href="#"
                                class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                <x-heroicon-o-document-text class="h-4 w-4" /> Dokumentasi
                            </a>
                            <a href="#"
                                class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">
                                <x-heroicon-o-chat-bubble-left-right class="h-4 w-4" /> Kontak Support
                            </a>
                        </div>
                    </div>

                    <!-- optional: grup terbaru -->
                    @isset($recentGroups)
                        <div class="rounded-2xl bg-white ring-1 ring-slate-200 p-5 shadow">
                            <div class="mb-3 flex items-center gap-2">
                                <x-heroicon-o-clock class="h-5 w-5 text-emerald-600" />
                                <h3 class="text-sm font-semibold text-slate-800">Baru dibuat</h3>
                            </div>
                            <ul class="space-y-2">
                                @forelse($recentGroups as $g)
                                    <li class="flex items-center justify-between text-sm">
                                        <span class="truncate text-slate-700">{{ $g->nama }}</span>
                                        <span class="font-mono text-xs text-slate-400">{{ $g->kode }}</span>
                                    </li>
                                @empty
                                    <li class="text-sm text-slate-500">Belum ada data.</li>
                                @endforelse
                            </ul>
                        </div>
                    @endisset
                </aside>

            </div>
        </div>
    </div>
@endsection
