@extends('layouts.app')

@section('title', $pageTitle ?? 'Edit Bayar Supplier')

@section('content')
    @include('bayarsupplier._form')
@endsection
