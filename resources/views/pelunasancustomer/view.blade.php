@extends('layouts.app')

@section('title', $pageTitle ?? 'View Pelunasan Customer')

@section('content')
    @include('pelunasancustomer._form')
@endsection
