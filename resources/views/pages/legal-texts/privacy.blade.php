@extends('layouts.legal-texts')

@section('title')
    {{ __('legal-texts.privacy-title') }}
@endsection

@section('content')
    {!! Str::markdown($privacy->description) !!}
@endsection