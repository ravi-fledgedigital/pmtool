<?php

namespace OnitsukaTiger\Restock\Model;

use OnitsukaTiger\Restock\Api\Data\GridInterface;
use OnitsukaTiger\Restock\Model\ResourceModel;

class GridRestock extends \Magento\Framework\Model\AbstractModel implements GridInterface
{
    /**
     * CMS page cache tag.
     */
    const CACHE_TAG = 'product_alert_stock_grid';

    /**
     * @var string
     */
    protected $_cacheTag = 'product_alert_stock_grid';

    /**
     * Prefix of model events names.
     *
     * @var string
     */
    protected $_eventPrefix = 'product_alert_stock_grid';

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init("OnitsukaTiger\Restock\Model\ResourceModel\GridRestock");
    }

    /**
     * Get alertStockId.
     *
     * @return int
     */
    public function getAlertStockId()
    {
        return $this->getData(self::ALERT_STOCK_ID);
    }

    /**
     * Set alertStockId.
     */
    public function setAlertStockId($alertStockId)
    {
        return $this->setData(self::ALERT_STOCK_ID, $alertStockId);
    }

    /**
     * Get productId.
     *
     * @return varchar
     */
    public function getProductId()
    {
        return $this->getData(self::PRODUCT_ID);
    }

    /**
     * Set productId.
     */
    public function setProductId($productId)
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    /**
     * Get customerId.
     *
     * @return varchar
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * Set customerId.
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * Get productImage.
     *
     * @return varchar
     */
    public function getProductImage()
    {
        return $this->getData(self::PRODUCT_IMAGE);
    }

    /**
     * Set productImage.
     */
    public function setProductImage($productImage)
    {
        return $this->setData(self::PRODUCT_IMAGE, $productImage);
    }

    /**
     * Get productName.
     *
     * @return varchar
     */
    public function getProductName()
    {
        return $this->getData(self::PRODUCT_NAME);
    }

    /**
     * Set ProductName.
     */
    public function setProductName($productName)
    {
        return $this->setData(self::PRODUCT_NAME, $productName);
    }

    /**
     * Get ProductType.
     *
     * @return varchar
     */
    public function getProductType()
    {
        return $this->getData(self::PRODUCT_TYPE);
    }

    /**
     * Set ProductType.
     */
    public function setProductType($productType)
    {
        return $this->setData(self::PRODUCT_TYPE, $productType);
    }

    /**
     * Get ProductSku.
     *
     * @return varchar
     */
    public function getProductSku()
    {
        return $this->getData(self::PRODUCT_SKU);
    }

    /**
     * Set ProductSku.
     */
    public function setProductSku($productSku)
    {
        return $this->setData(self::PRODUCT_SKU, $productSku);
    }

    /**
     * Get ProductPrice.
     *
     * @return varchar
     */
    public function getProductPrice()
    {
        return $this->getData(self::PRODUCT_PRICE);
    }

    /**
     * Set ProductPrice.
     */
    public function setProductPrice($productPrice)
    {
        return $this->setData(self::PRODUCT_PRICE, $productPrice);
    }

    /**
     * Get ProductQty.
     *
     * @return varchar
     */
    public function getProductQty()
    {
        return $this->getData(self::PRODUCT_QTY);
    }

    /**
     * Set ProductQty.
     */
    public function setProductQty($productQty)
    {
        return $this->setData(self::PRODUCT_QTY, $productQty);
    }

    /**
     * Get WebsiteId.
     *
     * @return varchar
     */
    public function getWebsiteId()
    {
        return $this->getData(self::WEBSITE_ID);
    }

    /**
     * Set WebsiteId.
     */
    public function setWebsiteId($websiteId)
    {
        return $this->setData(self::WEBSITE_ID, $websiteId);
    }

    /**
     * Get StoreId.
     *
     * @return varchar
     */
    public function getStoreId()
    {
        return $this->getData(self::STORE_ID);
    }

    /**
     * Set StoreId.
     */
    public function setStoreId($storeId)
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * Get AddDate.
     *
     * @return varchar
     */
    public function getAddDate()
    {
        return $this->getData(self::ADD_DATE);
    }

    /**
     * Set AddDate.
     */
    public function setAddDate($addDate)
    {
        return $this->setData(self::ADD_DATE, $addDate);
    }

    /**
     * Get SendDate.
     *
     * @return varchar
     */
    public function getSendDate()
    {
        return $this->getData(self::SEND_DATE);
    }

    /**
     * Set SendDate.
     */
    public function setSendDate($sendDate)
    {
        return $this->setData(self::SEND_DATE, $sendDate);
    }

    /**
     * Get SendCount.
     *
     * @return varchar
     */
    public function getSendCount()
    {
        return $this->getData(self::SEND_COUNT);
    }

    /**
     * Set SendCount.
     */
    public function setSendCount($sendCount)
    {
        return $this->setData(self::SEND_COUNT, $sendCount);
    }

    /**
     * Get Status.
     *
     * @return varchar
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * Set Status.
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Get AlertType.
     *
     * @return varchar
     */
    public function getAlertType()
    {
        return $this->getData(self::ALERT_TYPE);
    }

    /**
     * Set AlertType.
     */
    public function setAlertType($alertType)
    {
        return $this->setData(self::ALERT_TYPE, $alertType);
    }

    /**
     * Get LastArrivalContactDate.
     *
     * @return varchar
     */
    public function getLastArrivalContactDate()
    {
        return $this->getData(self::LAST_ARRIVAL_CONTACT_DATE);
    }

    /**
     * Set LastArrivalContactDate.
     */
    public function setLastArrivalContactDate($lastArrivalContactDate)
    {
        return $this->setData(self::LAST_ARRIVAL_CONTACT_DATE, $lastArrivalContactDate);
    }

    /**
     * Get TotalNumberRestock.
     *
     * @return varchar
     */
    public function getTotalNumberRestock()
    {
        return $this->getData(self::TOTAL_NUMBER_RESTOCK);
    }

    /**
     * Set TotalNumberRestock.
     */
    public function setTotalNumberRestock($totalNumberRestock)
    {
        return $this->setData(self::TOTAL_NUMBER_RESTOCK, $totalNumberRestock);
    }

    /**
     * Get RestockNotSent.
     *
     * @return varchar
     */
    public function getRestockNotSent()
    {
        return $this->getData(self::RESTOCK_NOT_SENT);
    }

    /**
     * Set RestockNotSent.
     */
    public function setRestockNotSent($restockNotSent)
    {
        return $this->setData(self::RESTOCK_NOT_SENT, $restockNotSent);
    }

    /**
     * Get RestockSent.
     *
     * @return varchar
     */
    public function getRestockSent()
    {
        return $this->getData(self::RESTOCK_SENT);
    }

    /**
     * Set RestockSent.
     */
    public function setRestockSent($restockSent)
    {
        return $this->setData(self::RESTOCK_SENT, $restockSent);
    }
}