<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Block\Order\Info\Buttons;

use Amasty\Rma\Controller\RegistryConstants;
use Amasty\Rma\Model\ConfigProvider;
use Magento\Customer\Model\Session;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class CreateReturn extends Template
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var int
     */
    private $orderId;

    public function __construct(
        Template\Context $context,
        ConfigProvider $configProvider,
        Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->configProvider = $configProvider;
        $this->registry = $registry;
    }

    public function toHtml()
    {
        /** @var \Magento\Sales\Api\Data\OrderInterface $order */
        if ($this->configProvider->isEnabled() && ($order = $this->registry->registry('current_order'))) {
            $allowedStatuses = $this->configProvider->getAllowedOrderStatuses();
            if (empty($allowedStatuses) || in_array($order->getStatus(), $allowedStatuses)) {
                $this->orderId = $order->getEntityId();
                return parent::toHtml();
            }
        }

        return '';
    }

    public function getCreateReturnUrl()
    {
        if ($this->orderId) {
            return $this->_urlBuilder->getUrl(
                $this->configProvider->getUrlPrefix() . '/account/newreturn',
                ['order' => $this->orderId]
            );
        }
        return false;
    }
}
