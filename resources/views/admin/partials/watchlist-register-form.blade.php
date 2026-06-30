<form method="post" action="{{ $action }}" class="action-form">
    @csrf
    <h3>要注意リストに登録</h3>
    <label>
        理由（注文画面に表示されます）
        <textarea name="reason" rows="3" required maxlength="2000">{{ old('reason') }}</textarea>
    </label>
    <button type="submit" onclick="return confirm('要注意リストに登録しますか？')">登録</button>
</form>
