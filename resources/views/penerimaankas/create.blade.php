@extends('layouts.app')

@section('title', $pageTitle)

@section('content')
    @include('pengeluarankas._form', [
        'transactionLabel' => 'Penerimaan Kas/Bank',
        'backRoute' => route('penerimaankas.index'),
    ])
@endsection
