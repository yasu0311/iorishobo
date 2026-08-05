@php
    use App\Support\Prefectures;
    $prefectureValue = old($name, $value ?? '');
    $invalid = $errors->has($name);
@endphp
<select name="{{ $name }}" @if (!empty($required)) required @endif @if ($invalid) aria-invalid="true" @endif>
    <option value="">選択してください</option>
    @foreach (Prefectures::all() as $prefecture)
        <option value="{{ $prefecture }}" @selected($prefectureValue === $prefecture)>{{ $prefecture }}</option>
    @endforeach
</select>
