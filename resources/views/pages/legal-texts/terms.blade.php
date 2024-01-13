@extends('layouts.legal-texts')

@section('title')
    {{ __('legal-texts.terms-title') }}
@endsection

@section('content')
    {!! Str::markdown($terms->description) !!}
@endsection