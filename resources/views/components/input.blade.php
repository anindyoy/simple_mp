@props([
    'label',
    'name',
    'type' => 'text',
    'autocomplete' => null,
    'value' => null,
])

@php
    $inputValue = $type === 'password' ? null : old($name, $value);
@endphp

<div>
    <label class="block text-sm mb-1">{{ $label }}</label>
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        data-label="{{ $label }}"
        @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if(! is_null($inputValue)) value="{{ $inputValue }}" @endif
        oninvalid="this.setCustomValidity(this.validity.valueMissing ? (this.dataset.label + ' wajib diisi.') : (this.validity.typeMismatch && this.type === 'email') ? 'Format email harus valid, contoh: nama@email.com.' : this.validity.patternMismatch ? (this.title || ('Format ' + this.dataset.label + ' tidak valid.')) : this.validity.tooShort ? (this.dataset.label + ' minimal ' + this.minLength + ' karakter.') : '')"
        oninput="this.setCustomValidity('')"
        required
        {{ $attributes->merge(['class' => 'w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500']) }}>

    @error($name)
        <div class="text-sm text-red-500 mt-2">
            {{ $message }}
        </div>
    @enderror
</div>
