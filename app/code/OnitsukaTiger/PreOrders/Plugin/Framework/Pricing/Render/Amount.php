<?php

namespace OnitsukaTiger\PreOrders\Plugin\Framework\Pricing\Render;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Pricing\Render\Amount as MagentoAmount;
use OnitsukaTiger\PreOrders\Helper\Data;
use OnitsukaTiger\PreOrders\Helper\PreOrder;
use OnitsukaTiger\PreOrders\Helper\Source\PreOrderNoteScope as PreOrderNoteScopeSourceInterface;

class Amount
{
    const PRODUCT_VIEW_PATH = 'catalog/product/view';
    /**
     * @var Data
     */
    private $helperIsModuleEnable;
    /**
     * @var Http
     */
    private $request;
    /**
     * @var PreOrder
     */
    protected $preOrder;

    /**
     * Amount constructor.
     *
     * @param SettingsHelper $settingsHelper
     * @param Http $request
     * @param Stock $stock
     */
    public function __construct(
        Data     $helperIsModuleEnable,
        PreOrder $preOrder,
        Http     $request,
    )
    {
        $this->request = $request;
        $this->helperIsModuleEnable = $helperIsModuleEnable;
        $this->preOrder = $preOrder;
    }

    /**
     * After tohtml
     *
     * @param MagentoAmount $subject
     * @param array $result
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterToHtml(MagentoAmount $subject, $result)
    {
        $storeId = $this->preOrder->getStoreId();
        if($this->helperIsModuleEnable->isModuleEnabled($storeId)){
            if($this->canBeApplied()){
                $message = '';
                $product = $subject->getSaleableItem();
                $productId = $product->getId();
                if ($this->preOrder->isProductPreOrder($productId)) {
                    $message = '(' . $this->preOrder->getPreOrderStatusLabelByProductId($productId) . ')';
                }
                $result .= '<br/><span style="font-size: 0.85em;font-weight: bold;" class="preorder_note">' . $message . '</span>';
            }
        }
        return $result;
    }

    /**
     * Get current scope
     *
     * @return int|string
     */
    private function getCurrentScope()
    {
        $scope = '';
        $request = $this->request;
        $requestPath = $request->getRouteName() . '/' . $request->getControllerName() . '/' . $request->getActionName();
        switch ($requestPath) {
            case self::PRODUCT_VIEW_PATH:
                $scope = PreOrderNoteScopeSourceInterface::PRODUCT_PAGE_SCOPE;
                break;
        }
        return $scope;
    }

    /**
     * Can be applied
     *
     * @return bool
     */
    private function canBeApplied()
    {
        $scope = $this->getCurrentScope();
        if ($scope == 2) {
            return true;
        }
        return false;
    }
}
