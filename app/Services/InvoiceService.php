<?php

namespace App\Services;

use App\Models\Invoice;

class InvoiceService
{
    public function createInvoice(string $itemType, int $itemId, float $amount, int $userId, string $description = null): Invoice
    {
        return Invoice::create([
            'description' => $description ?? "فاتورة مرتبطة بـ {$itemType} رقم {$itemId}",
            'date' => now(),
            'price' => $amount,
            'status' => 'unpaid',
            'item_type' => $itemType,
            'item_id' => $itemId,
            'user_id' => $userId,
        ]);
    }

    public function getInvoiceByItem($type, $itemId)
    {
        return Invoice::where('item_type', $type)
        ->where('item_id', $itemId)
        ->first();
    }

    public function updateInvoice(string $itemType, int $itemId, float $amount, string $description = null): ?Invoice
    {
        $invoice = $this->getInvoiceByItem($itemType, $itemId);

        if (!$invoice) {
            return null;
        }

        $invoice->update([
            'price' => $amount,
            'description' => $description ?? $invoice->description,
        ]);

        return $invoice;
    }

}
