@extends('layouts.app')

@section('title', $pageTitle ?? 'Bayar Supplier')

@section('content')
    @include('bayarsupplier._form')
@endsection
