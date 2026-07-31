<?php

namespace App\Services\Order;

use App\Models\EmailTemplate;
use App\Models\Order;

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

    public function subject(Order $order, bool $partial): string
    {
        $template = EmailTemplate::findBySlug(
            $partial ? 'order-partially-shipped' : 'order-shipped'
        );

        if (filled($template?->subject)) {
            return $template->subject.'　'.config('shop.name');
        }

        $label = $partial
            ? 'ご注文の一部を発送しました'
            : '商品の発送について';

        return $label.'　'.config('shop.name');
    }

    public function body(Order $order, bool $partial): string
    {
        $order->loadMissing('items');

        $unit = config('shop.quantity_unit');
        $template = EmailTemplate::findBySlug(
            $partial ? 'order-partially-shipped' : 'order-shipped'
        );

        $lines = [];

        if (filled($template?->body)) {
            $lines[] = trim($template->body);
        } elseif ($partial) {
            $lines[] = 'ご注文の一部を発送いたしました。';
            $lines[] = '残りの商品は準備ができ次第、あらためて発送いたします。';
        } else {
            $lines[] = 'ご注文の商品を発送いたしました。';
        }

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
