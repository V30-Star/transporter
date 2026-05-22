@extends('layouts.app')

@section('title', $pageTitle ?? 'Hapus Pelunasan Customer')

@section('content')
    @include('pelunasancustomer._form')
@endsection
