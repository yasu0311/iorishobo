<?php

namespace App\Services\Order;

use App\Exceptions\MissingEmailTemplateException;
use App\Models\EmailTemplate;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class OrderShippingMailComposer
{
    /**
     * @return array{subject: string, body: string}
     */
    public function template(Order $order, bool $partial): array
    {
        return [
            'subject' => $this->subject($order, $partial),
            'body' => $this->body($order, $partial),
        ];
    }

    /**
     * 注文詳細の編集用。テンプレート欠落時も画面を落とさず空欄＋エラーメッセージを返す。
     *
     * @return array{subject: string, body: string, error: ?string}
     */
    public function templateForEditor(Order $order, bool $partial): array
    {
        try {
            $template = $this->template($order, $partial);

            return [
                'subject' => $template['subject'],
                'body' => $template['body'],
                'error' => null,
            ];
        } catch (MissingEmailTemplateException $exception) {
            Log::warning('mail.template_unavailable_for_editor', [
                'order_id' => $order->id,
                'partial' => $partial,
                'error' => $exception->getMessage(),
            ]);

            return [
                'subject' => '',
                'body' => '',
                'error' => $exception->getMessage(),
            ];
        }
    }

    public function subject(Order $order, bool $partial): string
    {
        $template = EmailTemplate::requireBySlug(
            $partial ? 'order-partially-shipped' : 'order-shipped'
        );

        return $template->subject.'　'.config('shop.name');
    }

    public function body(Order $order, bool $partial): string
    {
        $order->loadMissing([
            'items.productVariant.product',
            'customer',
        ]);

        $template = EmailTemplate::requireBySlug(
            $partial ? 'order-partially-shipped' : 'order-shipped'
        );

        $buyerNameKana = $order->customer?->name_kana;
        $greeting = $order->buyer_name
            .($buyerNameKana ? '（'.$buyerNameKana.'）' : '')
            .' 様';

        $details = view('mail.partials.order-details', [
            'order' => $order,
            'trackingPlaceholder' => true,
            'showShippedAt' => ! $partial && $order->shipped_at !== null,
        ])->render();

        return $greeting."\n\n".trim($template->body)."\n\n".trim($details)."\n";
    }
}
