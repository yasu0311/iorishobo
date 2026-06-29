@extends('layouts.front')

@section('title', 'お支払い - '.config('shop.name'))

@section('content')
    <h1>クレジットカード決済</h1>
    <p>注文番号: {{ $order->order_number }}</p>
    <p>お支払い金額: {{ number_format($order->total) }}円（税込）</p>

    <form id="payment-form">
        <div id="payment-element"></div>
        <p id="payment-message" role="alert"></p>
        <button type="submit" id="submit">支払う</button>
    </form>

    @if (config('services.stripe.key'))
        <script src="https://js.stripe.com/v3/"></script>
        <script>
            const stripe = Stripe(@json(config('services.stripe.key')));
            const clientSecret = @json($clientSecret);

            const elements = stripe.elements({ clientSecret });
            const paymentElement = elements.create('payment');
            paymentElement.mount('#payment-element');

            const form = document.getElementById('payment-form');
            const message = document.getElementById('payment-message');

            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                const { error } = await stripe.confirmPayment({
                    elements,
                    confirmParams: {
                        return_url: @json(route('checkout.complete')),
                    },
                });

                if (error) {
                    message.textContent = error.message ?? '決済に失敗しました。';
                }
            });
        </script>
    @else
        <p>Stripe の設定がありません。管理者にお問い合わせください。</p>
    @endif
@endsection
