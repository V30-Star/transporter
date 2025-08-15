@extends('layouts.app')

@section('content')
    <script>
        window.location = "{{ route('login') }}";
    </script>
@endsection
