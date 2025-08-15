@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="bg-white p-6 rounded shadow">
        {{-- Selamat datang, {{ Auth::user()->name }}! --}}
        <p>Selamat datang, {{ session('fname') }}!</p>
        <p>Level pengguna: {{ session('fuserlevel') }}</p>
        <p>Cabang: {{ session('fcabang') }}</p>
    </div>
@endsection
