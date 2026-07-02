<form method="post" action="{{ $action }}">
    @csrf
    <h3 class="form-section__title">要注意リストに登録</h3>
    <div class="form-field">
        <label for="watchlist-reason">理由 <span class="form-hint">注文画面に表示されます</span></label>
        <textarea id="watchlist-reason" name="reason" rows="3" required maxlength="2000">{{ old('reason') }}</textarea>
    </div>
    <div class="form-actions">
        <button type="submit" onclick="return confirm('要注意リストに登録しますか？')">登録</button>
    </div>
</form>
