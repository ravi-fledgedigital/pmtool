<?php
namespace OnitsukaTiger\Gthk\Api;

interface CallbackInterface
{
    /**
     * Handle COD webhook from GHTK
     *
     * @return string
     */
    public function handleWebhook();
}
