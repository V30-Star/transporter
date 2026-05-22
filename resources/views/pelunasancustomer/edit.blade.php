@extends('layouts.app')

@section('title', $pageTitle ?? 'Edit Pelunasan Customer')

@section('content')
    @include('pelunasancustomer._form')
@endsection
