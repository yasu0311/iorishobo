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
        $order->loadMissing('items');

        $unit = config('shop.quantity_unit');
        $template = EmailTemplate::requireBySlug(
            $partial ? 'order-partially-shipped' : 'order-shipped'
        );

        $lines = [];
        $lines[] = trim($template->body);
        $lines[] = '';
        $lines[] = '注文番号: '.$order->order_number;

        if ($order->shipped_at !== null && ! $partial) {
            $lines[] = '発送日時: '.$order->shipped_at->format('Y-m-d H:i');
        }

        $lines[] = '{{TRACKING_LINE}}';

        $lines[] = '';
        $lines[] = '【ご注文内容】';

        foreach ($order->items as $item) {
            $label = $item->product_name;
            if (filled($item->variant_label)) {
                $label .= '（'.$item->variant_label.'）';
            }
            $lines[] = '- '.$label.' × '.$item->quantity.$unit;
        }

        $lines[] = '';
        $lines[] = '【配送先】';
        $lines[] = $order->shipping_name;
        $lines[] = '〒'.$order->shipping_postal_code.' '.$order->shipping_prefecture.$order->shipping_address_line1
            .(filled($order->shipping_address_line2) ? ' '.$order->shipping_address_line2 : '');
        $lines[] = '';
        $lines[] = '================================';
        $lines[] = config('shop.name').'　'.config('app.url');
        $lines[] = '================================';

        return implode("\n", $lines);
    }
}
