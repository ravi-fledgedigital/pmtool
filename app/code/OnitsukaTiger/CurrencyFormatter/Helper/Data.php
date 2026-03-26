<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace OnitsukaTiger\CurrencyFormatter\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;


class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * Get store config for currency format in BE product detail
     * @param $storeId
     * @return mixed
     */
    public function isEnableCurrencyFormatter($storeId): mixed
    {
        return $this->scopeConfig->getValue(
            'mpcurrencyformatter/general/enabled_in_store',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
