@extends('layouts.app')

@section('title', $action === 'delete' ? 'Hapus Penerimaan Barang' : 'Edit Penerimaan Barang')

@section('content')
    @include('penerimaanbarang._form')
@endsection
