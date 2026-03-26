<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Block\Returns;

use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Model\ReturnableOrdersProvider;
use Magento\Customer\Model\Session;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;

class OrderList extends Template
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var bool
     */
    private $isGuest;

    /**
     * @var ReturnableOrdersProvider
     */
    private $returnableOrdersProvider;

    public function __construct(
        Template\Context $context,
        ConfigProvider $configProvider,
        Session $customerSession,
        PriceCurrencyInterface $priceCurrency,
        ReturnableOrdersProvider $returnableOrdersProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->configProvider = $configProvider;
        $this->customerSession = $customerSession;
        $this->priceCurrency = $priceCurrency;
        $this->returnableOrdersProvider = $returnableOrdersProvider;
        $this->isGuest = !empty($data['isGuest']);
    }

    /**
     * @return array
     */
    public function getOrders()
    {
        if (!$this->isGuest()) {
            $customerId = (int)$this->customerSession->getCustomerId();
        } else {
            $customerId = 0;
        }

        return $this->returnableOrdersProvider->getOrders($customerId);
    }

    public function formatPrice($price)
    {
        return $this->priceCurrency->convertAndFormat(
            $price,
            false,
            2
        );
    }

    public function getNewReturnUrl()
    {
        if (!$this->isGuest()) {
            return $this->_urlBuilder->getUrl($this->configProvider->getUrlPrefix() .'/account/newreturn');
        }

        return $this->_urlBuilder->getUrl($this->configProvider->getUrlPrefix() . '/guest/loginpost');
    }

    public function getLogoutUrl()
    {
        return $this->_urlBuilder->getUrl($this->configProvider->getUrlPrefix() . '/guest/logout');
    }

    /**
     * @return bool
     */
    public function isGuest()
    {
        return $this->isGuest;
    }
}
