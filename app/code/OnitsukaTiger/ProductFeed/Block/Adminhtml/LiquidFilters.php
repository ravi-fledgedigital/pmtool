<?php
namespace OnitsukaTiger\ProductFeed\Block\Adminhtml;

use Magento\Framework\Exception\NoSuchEntityException;

class LiquidFilters extends \Mageplaza\ProductFeed\Block\Adminhtml\LiquidFilters
{
    public $context;
    /**
     * @var int
     */
    private $storeId;

    /**
     * @param $storeId
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
    }

    /**
     * @Override
     * @param $subject
     * @param null $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    public function price($subject, $storeId = null)
    {
        return floor($subject) . ' ' . $this->storeManager->getStore($this->storeId)->getCurrentCurrencyCode();
    }
}
