<?php

namespace OnitsukaTigerKorea\Rma\Helper;

use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Model\Request\ResourceModel\CollectionFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\Registry;
use OnitsukaTiger\OrderStatusTracking\Helper\Data as TrackingHelper;
use OnitsukaTiger\Rma\Helper\Data;

class RmaGuest extends Data
{
    /**
     * @var PostHelper
     */
    private $postHelper;

    /**
     * @var CollectionFactory
     */
    private $rmaCollectionFactory;

    /**
     * @var ConfigProvider
     */
    private $configProvider;


    /**
     * Guest constructor.
     * @param ConfigProvider $configProvider
     * @param Http $request
     * @param ScopeConfigInterface $scopeConfig
     * @param TrackingHelper $helperTrack
     * @param Configurable $configurable
     * @param Registry $registry
     * @param Context $context
     * @param PostHelper $postHelper
     * @param CollectionFactory $rmaCollectionFactory
     */
    public function __construct(
        ConfigProvider $configProvider,
        Http $request,
        ScopeConfigInterface $scopeConfig,
        TrackingHelper $helperTrack,
        Configurable $configurable,
        Registry $registry,
        Context $context,
        PostHelper $postHelper,
        CollectionFactory $rmaCollectionFactory
    ) {
        $this->postHelper = $postHelper;
        $this->rmaCollectionFactory = $rmaCollectionFactory;
        $this->configProvider = $configProvider;
        parent::__construct(
            $configProvider,
            $request,
            $scopeConfig,
            $helperTrack,
            $configurable,
            $registry,
            $context
        );
    }

    /**
     * @param $order
     * @return bool
     */
    public function checkButtonReorderForGuest($order)
    {
        if ($this->configProvider->isGuestRmaAllowed()) {
            return $this->checkButtonReorder($order);
        }
        return false;
    }

    /**
     * @return string
     */
    public function getActionLoginUrlForGuest($order)
    {
        $parameters = [
            '_secure' => true,
            'oar_order_id' => $order->getIncrementId(),
            'oar_email' => $order->getCustomerEmail(),
            'oar_billing_lastname' => $order->getBillingAddress()->getLastname(),
        ];
        return $this->postHelper->getPostData(
            $this->_urlBuilder->getUrl(
                $this->configProvider->getUrlPrefix() . '/guest/loginPost'
            ),
            $parameters
        );
    }

    /**
     * @param $orderId
     * @return mixed
     */
    public function getRmaByOrderId($orderId)
    {
        $collection = $this->rmaCollectionFactory->create();
        $collection->addFieldToFilter('order_id', $orderId);
        return $collection->getItems();
    }

    /**
     * @param $hash
     * @return string
     */
    public function getRmaUrlByHash($hash)
    {
        return $this->_urlBuilder->getUrl(
            $this->configProvider->getUrlPrefix() . '/guest/view',
            ['request' => $hash]
        );
    }
}
