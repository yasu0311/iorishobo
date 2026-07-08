<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>商品</th>
                <th>単価</th>
                <th>数量（{{ config('shop.quantity_unit') }}）</th>
                <th>小計</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($summary->lines as $line)
                <tr>
                    <td>
                        {{ $line->product->name }}
                        @if ($line->variant->name !== $line->product->name)
                            <br><span class="text-muted">{{ $line->variant->name }}</span>
                        @endif
                    </td>
                    <td>{{ number_format($line->unitPrice) }}円</td>
                    <td><x-quantity :value="$line->item->quantity" /></td>
                    <td>{{ number_format($line->lineSubtotal) }}円</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
