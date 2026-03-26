<?php
namespace OnitsukaTiger\PortOne\Api;

interface WebhookDataInterface
{
    /**
     * Manage webhook payload.
     *
     * @param string $tx_id
     * @param string $payment_id
     * @param string $status
     * @return string
     */
    public function handleWebhookData(
        string $tx_id,
        string $payment_id,
        string $status
    ): string;
}
