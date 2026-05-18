@extends('layouts.app')

@section('title', $action === 'delete' ? 'Hapus Permintaan Pembelian' : 'Edit Permintaan Pembelian')

@section('content')
    @include('tr_prh._form')
@endsection
