@props(['name', 'label' => null])

@php
    $invalid = $errors->has($name);
@endphp

<div {{ $attributes->class(['form-field', 'form-field--invalid' => $invalid]) }}>
    @if ($label !== null)
        <label>{{ $label }}</label>
    @endif
    {{ $slot }}
    <x-input-error :messages="$errors->get($name)" />
</div>
