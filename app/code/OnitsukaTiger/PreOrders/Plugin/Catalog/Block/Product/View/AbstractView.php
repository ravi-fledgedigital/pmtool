<?php

namespace OnitsukaTiger\PreOrders\Plugin\Catalog\Block\Product\View;

use OnitsukaTiger\PreOrders\Helper\Source\PreOrderNoteScope as PreOrderNoteScopeSourceInterface;
use OnitsukaTiger\PreOrders\Helper\PreOrder;
use OnitsukaTiger\PreOrders\Helper\Data;
use Magento\Catalog\Block\Product\View\AbstractView as ProductViewAbstractView;

class AbstractView
{
    const SIMPLE_PRODUCT_INFO_BLOCK_NAME = "product.info.simple";

    /**
     * @var Data
     */
    protected $helperIsModuleEnable;

    /**
     * @var PreOrder
     */
    protected $preOrder;

    /**
     * AbstractView constructor.
     *
     * @param SettingsHelper $settingsHelper
     * @param PreOrder $stock
     */
    public function __construct(
        Data $helperIsModuleEnable,
        PreOrder $preOrder
    ) {
        $this->helperIsModuleEnable = $helperIsModuleEnable;
        $this->preOrder = $preOrder;
    }

    /**
     * After tohtml
     *
     * @param ProductViewAbstractView $subject
     * @param array $result
     * @return mixed|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterToHtml(
        ProductViewAbstractView $subject,
        $result
    ) {
        $product = $subject->getProduct();
        $isPreOrder = $this->preOrder->isProductPreOrder($product->getId());
        if ($isPreOrder) {
            switch ($subject->getNameInLayout()) {
                case self::SIMPLE_PRODUCT_INFO_BLOCK_NAME:
                    if (in_array(PreOrderNoteScopeSourceInterface::PRODUCT_PAGE_SCOPE, 2)) {
                        $result = $this->changeStatusText($result, $product->getId());
                    }
                    break;
            }
        }
        return $result;
    }

    /**
     * Change status text
     *
     * @param string $html
     * @param int $productId
     * @return array|string|string[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function changeStatusText($html, $productId)
    {
        $statusArray = [__('In stock'), __('Out of stock')];
        $preOrderStatus = $this->preOrder->getPreOrderStatusLabelByProductId($productId);
        $html = str_replace($statusArray, $preOrderStatus, $html);
        return $html;
    }
}
