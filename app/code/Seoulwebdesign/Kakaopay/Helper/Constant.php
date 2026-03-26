<?php

namespace Seoulwebdesign\Kakaopay\Helper;

class Constant
{
    const KAKAOPAY_APPROVAL_URL = 'kakaopay/checkout/approval';
    const KAKAOPAY_CANCEL_URL = 'kakaopay/checkout/cancel';
    const KAKAOPAY_FAIL_URL = 'kakaopay/checkout/fail';
    const KAKAOPAY_RETRY_URL = 'kakaopay/checkout/retry';
    const KAKAOPAY_REDIRECT_URL = 'kakaopay/checkout/redirect';
    const KAKAOPAY_PAYMENT_READY = 'https://kapi.kakao.com/v1/payment/ready';
    const KAKAOPAY_PAYMENT_CHECK = 'https://kapi.kakao.com/v1/payment/status';
    const KAKAOPAY_PAYMENT_APPROVE = 'https://kapi.kakao.com/v1/payment/approve';
    const KAKAOPAY_PAYMENT_REFUND = 'https://kapi.kakao.com/v1/payment/cancel';

    const KAKAOPAY_RESPONSE_URL = 'kakaopay_response_url';
    const KAKAOPAY_MOBILE_RESPONSE_URL = 'kakaopay_mobile_response_url';
    const KAKAOPAY_RESPONSE_TID = 'kakaopay_response_tid';
    const KAKAOPAY_RESPONSE_TOKEN = 'kakaopay_response_token';
    const KAKAOPAY_RESPONSE_POI = 'kakaopay_response_poi';
    const KAKAOPAY_RESPONSE_PUI = 'kakaopay_response_pui';
    const KAKAOPAY_RESPONSE_PAYMENT_DETAIL = 'kakaopay_response_payment_detail';
}
