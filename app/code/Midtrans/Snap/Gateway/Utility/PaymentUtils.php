<?php

namespace Midtrans\Snap\Gateway\Utility;

class PaymentUtils
{
    public static function isOpenApi($paymentType): bool{
        if (empty($paymentType)) {
            return false;
        }
        return (strtolower($paymentType) == "dana");
    }
}
