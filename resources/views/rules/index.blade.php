@extends('layouts.app')

@section('title', 'Peraturan Pengguna | Jual Beli Cimanglid')

@section('content')
    <section class="py-10 px-4">
        <div class="max-w-4xl mx-auto bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm p-6 md:p-8">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                Peraturan Pengguna
            </h1>

            @if (blank($rulesContent))
                <p class="text-gray-600 dark:text-gray-300">
                    Peraturan pengguna belum tersedia.
                </p>
            @else
                <article class="rules-content max-w-none text-gray-800">
                    {!! $rulesContent !!}
                </article>
            @endif
        </div>
    </section>
@endsection
