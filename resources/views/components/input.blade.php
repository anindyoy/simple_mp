@props([
    'label',
    'name',
    'type' => 'text',
    'autocomplete' => null,
])

<div>
    <label class="block text-sm mb-1">{{ $label }}</label>
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        required
        class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
</div>
