@extends('layouts.front')

@section('title', 'お支払い - '.config('shop.name'))

@section('content')
    <div class="stripe-payment panel">
        <h1>クレジットカード決済</h1>
        <p>注文番号: <strong>{{ $order->order_number }}</strong></p>
        <p class="product-detail__price">お支払い金額: {{ number_format($order->total) }}円（税込）</p>

        @if (config('services.stripe.key'))
            <form id="payment-form">
                <div id="payment-element"></div>
                <p id="payment-message" role="alert"></p>
                <button type="submit" id="submit" class="btn btn--primary btn--block">注文を確定して支払う</button>
            </form>
        @else
            <x-alert type="error">Stripe の設定がありません。管理者にお問い合わせください。</x-alert>
        @endif
    </div>
@endsection

@if (config('services.stripe.key'))
@section('script')
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        const stripe = Stripe(@json(config('services.stripe.key')));
        const clientSecret = @json($clientSecret);

        const elements = stripe.elements({
            clientSecret,
            appearance: {
                theme: 'stripe',
                variables: {
                    colorPrimary: '#003b83',
                    borderRadius: '6px',
                },
            },
        });
        const paymentElement = elements.create('payment');
        paymentElement.mount('#payment-element');

        const form = document.getElementById('payment-form');
        const message = document.getElementById('payment-message');
        const submit = document.getElementById('submit');

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            submit.disabled = true;

            const { error } = await stripe.confirmPayment({
                elements,
                confirmParams: {
                    return_url: @json(route('checkout.complete')),
                },
            });

            if (error) {
                message.textContent = error.message ?? '決済に失敗しました。';
                submit.disabled = false;
            }
        });
    </script>
@endsection
@endif
