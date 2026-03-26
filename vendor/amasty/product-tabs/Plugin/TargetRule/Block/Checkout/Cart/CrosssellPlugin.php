<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Plugin\TargetRule\Block\Checkout\Cart;

use Magento\Catalog\Model\Product;
use Magento\TargetRule\Block\Checkout\Cart\Crosssell;

class CrosssellPlugin
{
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var Product
     */
    protected $product = null;

    public function __construct(\Magento\Framework\Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param Crosssell $subject
     * @param \Closure $proceed
     *
     * @return int
     */
    public function aroundGetLastAddedProductId(
        Crosssell $subject,
        \Closure $proceed
    ) {
        if (strpos($subject->getNameInLayout(), 'amcustomtabs') !== false && $this->getProduct()) {
            return $this->getProduct()->getId();
        }

        return $proceed();
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        if (!$this->product) {
            $this->product = $this->registry->registry('product');
        }

        return $this->product;
    }
}
