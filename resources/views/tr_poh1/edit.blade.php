@extends('layouts.app')

@section('title', $action === 'delete' ? 'Hapus Order Pembelian' : 'Edit Order Pembelian')

@section('content')
    @include('tr_poh._form')
@endsection
