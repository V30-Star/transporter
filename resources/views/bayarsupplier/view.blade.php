@extends('layouts.app')

@section('title', $pageTitle ?? 'View Bayar Supplier')

@section('content')
    @include('bayarsupplier._form')
@endsection
