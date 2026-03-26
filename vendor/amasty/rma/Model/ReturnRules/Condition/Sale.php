<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\ReturnRules\Condition;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\Rule\Model\Condition\Context;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory;

class Sale extends AbstractCondition
{
    /**
     * @var Yesno
     */
    private $yesno;

    /**
     * @var CollectionFactory
     */
    private $orderItemCollectionFactory;

    /**
     * @var Configurable
     */
    private $configurableInstance;

    public function __construct(
        Yesno $yesno,
        CollectionFactory $orderItemCollectionFactory,
        Configurable $configurableInstance,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->yesno = $yesno;
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->configurableInstance = $configurableInstance;
    }

    public function loadAttributeOptions()
    {
        $attributes = [
            'was_on_sale' => __('Was on sale (Special price / catalog rule)')
        ];
        $this->setAttributeOption($attributes);

        return $this;
    }

    /**
     * @return AbstractCondition
     */
    public function getAttributeElement()
    {
        $element = parent::getAttributeElement();
        $element->setShowAsText(true);

        return $element;
    }

    /**
     * @return string
     */
    public function getInputType()
    {
        return 'boolean';
    }

    /**
     * @return string
     */
    public function getValueElementType()
    {
        return 'select';
    }

    /**
     * @return array
     */
    public function getValueSelectOptions()
    {
        $key = 'value_select_options';

        if (!$this->hasData($key)) {
            $this->setData($key, $this->yesno->toOptionArray());
        }

        return $this->getData($key);
    }

    /**
     * Validate Was On Sale Condition
     *
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return bool
     */
    public function validate(\Magento\Framework\Model\AbstractModel $model)
    {
        $attributeValue = false;
        $orderId = $model->getOrderId();
        $productId = $model->getId();

        if ($orderId && $productId) {
            if ($parentIds = $this->configurableInstance->getParentIdsByChild($productId)) {
                $productId = $parentIds;
            }
            /** @var \Magento\Sales\Model\Order\Item $orderItem */
            $orderItem = $this->orderItemCollectionFactory->create()
                ->addFieldToFilter('order_id', $orderId)
                ->addFieldToFilter('product_id', ['in' => $productId])
                ->addFieldToFilter('sku', $model->getSku())
                ->getFirstItem();
            $attributeValue = (float)$orderItem->getOriginalPrice() !== (float)$orderItem->getPrice();
        } elseif ($productId && $model->getTypeId() == Configurable::TYPE_CODE) {
            // value for product page and more information tab
            $attributeValue = $this->validateConfigurableProduct($model);
        } else {
            // value for product page and more information tab
            $attributeValue = $model->getPrice() != $model->getFinalPrice();
        }

        return $this->validateAttribute((int)$attributeValue);
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel|\Magento\Catalog\Model\Product $product
     *
     * @return bool
     */
    private function validateConfigurableProduct($product)
    {
        $onSale = false;

        foreach ($product->getTypeInstance()->getUsedProducts($product) as $simpleProduct) {
            if ($simpleProduct->getPrice() != $simpleProduct->getFinalPrice()) {
                $onSale = true;
                break;
            }
        }

        return $onSale;
    }
}
