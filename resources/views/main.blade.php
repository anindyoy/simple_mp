@extends('layouts.app')
@section('title', $meta['title'])
@section('meta_description', $meta['description'])
@section('meta_keywords', $meta['keywords'])
@section('og_title', $meta['title'])
@section('og_description', $meta['description'])
@section('content')

    @if(config('app.demo_mode'))
        <div class="bg-yellow-50 border-b border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-800">
            <div class="container mx-auto px-4 py-3 flex items-center gap-3 text-sm text-yellow-800 dark:text-yellow-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <span>
                    <strong>Mode Demo</strong> — Ini adalah versi demo aplikasi. Login dan registrasi dinonaktifkan.
                    @if(env('APP_MAIN_URL'))
                        Kunjungi website utama di
                        <a href="{{ env('APP_MAIN_URL') }}" target="_blank" rel="noopener noreferrer"
                            class="underline font-semibold hover:text-yellow-900 dark:hover:text-yellow-100 transition">
                            {{ env('APP_MAIN_URL') }}
                        </a>.
                    @endif
                </span>
            </div>
        </div>
    @endif

    <div class="container mx-auto px-4 py-8">
        <livewire:product-catalog />
    </div>
@endsection
