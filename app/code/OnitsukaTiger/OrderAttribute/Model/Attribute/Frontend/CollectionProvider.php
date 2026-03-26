<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Attribute\Frontend;

use OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface;
use OnitsukaTiger\OrderAttribute\Model\Config\Source\CheckoutStep;
use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

class CollectionProvider
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var array
     */
    private $shippingAttributes = [];

    /**
     * @var array
     */
    private $paymentAttributes = [];

    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Collection
     */
    private $collection;

    public function __construct(
        StoreManagerInterface $storeManager,
        CollectionFactory $collectionFactory
    ) {
        $this->storeManager = $storeManager;
        $this->collection = $collectionFactory->create();
        $this->collection->setOrder(CheckoutAttributeInterface::SORTING_ORDER, 'ASC');
        $this->collection->addFieldToFilter(CheckoutAttributeInterface::IS_VISIBLE_ON_FRONT, 1);
    }

    /**
     * @return \OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface[]
     */
    public function getAttributes()
    {
        $this->collection->addStoreFilter($this->storeManager->getStore()->getId());

        return $this->collection->getItems();
    }

    /**
     * @return \OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface[]
     */
    public function getShippingAttributes()
    {
        if (!$this->shippingAttributes) {
            $this->shippingAttributes = $this->getAttributesForStep(CheckoutStep::SHIPPING_STEP);
        }

        return $this->shippingAttributes;
    }

    /**
     * @return \OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface[]
     */
    public function getPaymentAttributes()
    {
        if (!$this->paymentAttributes) {
            $this->paymentAttributes = $this->getAttributesForStep(CheckoutStep::PAYMENT_STEP);
        }

        return $this->paymentAttributes;
    }

    /**
     * @param $checkoutStep
     *
     * @return \OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface[]
     */
    public function getAttributesForStep($checkoutStep)
    {
        $result = [];

        foreach ($this->getAttributes() as $frontendAttribute) {
            if ((int)$frontendAttribute->getCheckoutStep() === $checkoutStep) {
                $result[] = $frontendAttribute;
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getAttributeCodes()
    {
        return $this->collection->getColumnValues('attribute_code');
    }
}
