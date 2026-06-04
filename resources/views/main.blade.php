@extends('layouts.app')
@section('title', $meta['title'])
@section('meta_description', $meta['description'])
@section('meta_keywords', $meta['keywords'])
@section('og_title', $meta['title'])
@section('og_description', $meta['description'])
@section('content')
    <div class="container mx-auto px-4 py-8">
        <livewire:product-catalog />
    </div>
@endsection
