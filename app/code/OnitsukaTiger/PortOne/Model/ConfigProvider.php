<?php
namespace OnitsukaTiger\PortOne\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use OnitsukaTiger\PortOne\Helper\Data;
use Magento\Store\Model\ScopeInterface;

class ConfigProvider implements ConfigProviderInterface
{
    protected Data $helperClass;

    public function __construct(Data $helperClass)
    {
        $this->helperClass = $helperClass;
    }

    public function getConfig()
    {
        return [
            'payment' => [
                'portone' => $this->helperClass->getPaymentConfig(Data::XML_PATH_PORTONE),
                'portonetransfer' => $this->helperClass->getPaymentConfig(Data::XML_PATH_TRANSFER),
                'portone_easypay' => $this->helperClass->getPaymentConfig(Data::XML_PATH_EASYPAY),
                'portone_tosspay' => $this->helperClass->getPaymentConfig(Data::XML_PATH_TOSSPAY),
                'portone_npay' => $this->helperClass->getPaymentConfig(Data::XML_PATH_NPAY),
                'portone_kakaopay' => $this->helperClass->getPaymentConfig(Data::XML_PATH_KAKAOPAY),
                'portonepaycopay' => $this->helperClass->getPaymentConfig(Data::XML_PATH_PAYCOPAY),
                'portonesamsungpay' => $this->helperClass->getPaymentConfig(Data::XML_PATH_SAMSUNGPAY),
                'portoneapplepay' => $this->helperClass->getPaymentConfig(Data::XML_PATH_APPLEPAY),
            ],
        ];
    }
}
