@extends('layouts.app')

@section('title', $action === 'delete' ? 'Hapus Faktur Pembelian' : 'Edit Faktur Pembelian')

@section('content')
    @include('fakturpembelian._form')
@endsection
