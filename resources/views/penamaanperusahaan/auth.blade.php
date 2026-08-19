@extends('layouts.app')

@section('title', $pageTitle)

@section('content')
    <div style="min-height: 65vh; display: flex; align-items: center; justify-content: center; padding: 24px 16px;">
        <div style="width: 100%; max-width: 440px;">
            <div style="background: #ffffff; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.05); border: 1px solid #e5e7eb; overflow: hidden; padding: 36px 32px; position: relative;">
                
                {{-- Accent top bar --}}
                <div style="position: absolute; top: 0; left: 0; right: 0; height: 5px; background: #2563eb;"></div>

                {{-- Shield Icon Header --}}
                <div style="display: flex; justify-content: center; margin-bottom: 20px;">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background: #eff6ff; display: flex; align-items: center; justify-content: center; color: #2563eb; border: 1px solid #dbeafe;">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width: 30px; height: 30px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                </div>

                {{-- Title & Subtitle --}}
                <div style="text-align: center; margin-bottom: 24px;">
                    <h1 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0;">{{ $pageTitle }}</h1>
                    <p style="font-size: 13px; color: #6b7280; margin-top: 8px; line-height: 1.5;">
                        Halaman ini diproteksi. Masukkan password otorisasi untuk mengelola identitas perusahaan.
                    </p>
                </div>

                {{-- Alert Error --}}
                @if(session('error'))
                    <div style="background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 10px; padding: 12px 14px; font-size: 13px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px; flex-shrink: 0;" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                {{-- Form --}}
                <form action="{{ route('penamaanperusahaan.verify') }}" method="POST" x-data="{ show: false }">
                    @csrf
                    <div style="margin-bottom: 20px;">
                        <label for="password" style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #374151; margin-bottom: 8px;">
                            Password Otorisasi
                        </label>
                        <div style="position: relative;">
                            <input :type="show ? 'text' : 'password'" id="password" name="password" required autofocus
                                style="width: 100%; border-radius: 10px; border: 1px solid #d1d5db; padding: 11px 44px 11px 14px; font-size: 14px; color: #111827; box-sizing: border-box; outline: none; transition: border-color 0.2s, box-shadow 0.2s;"
                                placeholder="Masukkan password...">
                            
                            <button type="button" @click="show = !show"
                                style="position: absolute; top: 0; right: 0; bottom: 0; padding: 0 14px; background: transparent; border: none; cursor: pointer; color: #6b7280; display: flex; align-items: center;"
                                :title="show ? 'Sembunyikan password' : 'Lihat password'">
                                <template x-if="!show">
                                    <svg xmlns="http://www.w3.org/2000/svg" style="width: 20px; height: 20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </template>
                                <template x-if="show">
                                    <svg xmlns="http://www.w3.org/2000/svg" style="width: 20px; height: 20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </template>
                            </button>
                        </div>
                        @error('password')
                            <p style="color: #ef4444; font-size: 12px; margin-top: 6px;">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Actions --}}
                    <div style="display: flex; gap: 12px; margin-top: 8px;">
                        <a href="{{ route('dashboard') }}"
                            style="width: 35%; display: inline-flex; justify-content: center; align-items: center; border-radius: 10px; background: #f3f4f6; border: 1px solid #d1d5db; padding: 11px 16px; font-size: 14px; font-weight: 600; color: #374151; text-decoration: none; box-sizing: border-box; cursor: pointer;">
                            Batal
                        </a>
                        <button type="submit"
                            style="width: 65%; display: inline-flex; justify-content: center; align-items: center; gap: 8px; border-radius: 10px; background: #2563eb; border: none; padding: 11px 16px; font-size: 14px; font-weight: 600; color: #ffffff; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.3);">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                            </svg>
                            Buka Akses
                        </button>
                    </div>
                </form>

                {{-- Encryption Note --}}
                <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #f3f4f6; text-align: center;">
                    <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 11px; color: #9ca3af;">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width: 13px; height: 13px; color: #2563eb;" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        Terenkripsi AES-256
                    </span>
                </div>
            </div>
        </div>
    </div>
@endsection
