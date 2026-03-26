<?php

namespace OnitsukaTigerVn\OnePay\Model;

use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

class OnepayRefund
{
    /** @var Curl */
    protected $curlClient;

    /** @var LoggerInterface */
    protected $logger;

    /** @var string */
    protected $secureSecret = 'REPLACE_WITH_YOUR_SECURE_SECRET';

    /** @var string */
    protected $url = 'https://onepay.vn/msp/api/v1/vpc/refunds';

    public function __construct(
        Curl $curl,
        LoggerInterface $logger,
        private \Ecomteck\OnePay\Helper\Data $onePayHelperData,
        private \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->curlClient = $curl;
        $this->logger = $logger;
    }

    public function refund($txnId, $amount, $order, $operator = 'admin')
    {
        $txnRef = date('YmdHis') . time();
        $websiteId = $order->getStore()->getWebsiteId();
        $accessCode = $this->getDomesticCardAccessCode($websiteId);
        $merchantId = $this->getDomesticCardMerchantId($websiteId);
        $hasCode = $this->getDomesticCardHashCode($websiteId);
        $finalAmount = $amount * 100;
        $params = [
            'vpc_Command' => 'refund',
            'vpc_Merchant' => $merchantId,
            'vpc_AccessCode' => $accessCode,
            'vpc_MerchTxnRef' => $txnRef,
            'vpc_OrgMerchTxnRef' => $txnId,
            'vpc_Amount' => $finalAmount,
            'vpc_Operator' => $operator,
            'vpc_Version' => '2'
        ];

        ksort($params);

        $hashString = urldecode(http_build_query($params));
        $secureHash = hash_hmac('SHA256', $hashString, pack('H*', $hasCode));

        $params['vpc_SecureHash'] = $secureHash;
        $parsedResponse = [];
        try {
            $headers = [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ];

            $this->curlClient->setHeaders($headers);
            $this->curlClient->setOptions([
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $postFields = http_build_query($params);
            $this->curlClient->post($this->url, $postFields);


            $response = $this->curlClient->getBody();
            $httpCode = $this->curlClient->getStatus();

            $this->logger->info('OnePAY Refund Request:', $params);
            $this->logger->info('OnePay Refund HTTP Code: ' . $httpCode);
            $this->logger->info('OnePAY Refund Response: ' . $response);
            parse_str($response, $parsedResponse);
            $this->logger->info("Parsed Response: " . print_r($parsedResponse, true));
            return $parsedResponse;
        } catch (\Exception $e) {
            $this->logger->error('OnePAY Refund Error: ' . $e->getMessage());
            return $parsedResponse;
        }
    }

    private function getDomesticCardAccessCode($websiteId)
    {
        return $this->scopeConfig->getValue(
            $this->onePayHelperData::ONEPAY_DOMESTIC_CARD_ACCESS_CODE,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    private function getDomesticCardMerchantId($websiteId)
    {
        return $this->scopeConfig->getValue(
            $this->onePayHelperData::ONEPAY_DOMESTIC_CARD_MERCHANT_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    private function getDomesticCardHashCode($websiteId)
    {
        return $this->scopeConfig->getValue(
            $this->onePayHelperData::ONEPAY_DOMESTIC_CARD_HASH_CODE,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }
}
