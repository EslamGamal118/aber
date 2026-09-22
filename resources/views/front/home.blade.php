@extends('front.layouts.master')

@section('title', 'عابر - منصة عربات الطعام الأولى في المملكة')

@section('content')
    @include('front.sections.hero')
    @include('front.sections.categories')
    @include('front.sections.food-trucks')
    @include('front.sections.features')
    @include('front.sections.cta')
@endsection 