@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="bg-white p-6 rounded shadow">
        Selamat datang, {{ Auth::user()->name }}!
    </div>
@endsection
