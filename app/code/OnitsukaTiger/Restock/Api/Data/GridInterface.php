<?php
namespace OnitsukaTiger\Restock\Api\Data;

interface GridInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case.
     */
    const ALERT_STOCK_ID = 'alert_stock_id';
    const CUSTOMER_ID = 'customer_id';
    const PRODUCT_ID = 'product_id';
    const PRODUCT_IMAGE = 'product_image';
    const PRODUCT_NAME = 'product_name';
    const PRODUCT_TYPE = 'product_type';
    const PRODUCT_SKU = 'product_sku';
    const PRODUCT_PRICE = 'product_price';
    const PRODUCT_QTY = 'product_qty';
    const WEBSITE_ID = 'website_id';
    const STORE_ID = 'store_id';
    const ADD_DATE = 'add_date';
    const SEND_DATE = 'send_date';
    const SEND_COUNT = 'send_count';
    const STATUS = 'status';
    const ALERT_TYPE = 'alert_type';
    const LAST_ARRIVAL_CONTACT_DATE = 'last_arrival_contact_date';
    const TOTAL_NUMBER_RESTOCK = 'total_number_restock';
    const RESTOCK_NOT_SENT = 'restock_not_sent';
    const RESTOCK_SENT = 'restock_sent';

    
    /**
     * Get alertStockId.
     *
     * @return int
     */
    public function getAlertStockId();

    /**
     * Set alertStockId.
     */
    public function setAlertStockId($alertStockId);

    /**
     * Get productId.
     *
     * @return varchar
     */
    public function getProductId();

    /**
     * Set productId.
     */
    public function setProductId($productId);

    /**
     * Get productId.
     *
     * @return varchar
     */
    public function getCustomerId();

    /**
     * Set customerId.
     */
    public function setCustomerId($customerId);

    /**
     * Get productImage.
     *
     * @return varchar
     */
    public function getProductImage();

    /**
     * Set productImage.
     */
    public function setProductImage($productImage);

    /**
     * Get productName.
     *
     * @return varchar
     */
    public function getProductName();

    /**
     * Set ProductName.
     */
    public function setProductName($productName);

    /**
     * Get ProductType.
     *
     * @return varchar
     */
    public function getProductType();

    /**
     * Set ProductType.
     */
    public function setProductType($productType);

    /**
     * Get ProductSku.
     *
     * @return varchar
     */
    public function getProductSku();

    /**
     * Set ProductSku.
     */
    public function setProductSku($productSku);

    /**
     * Get ProductPrice.
     *
     * @return varchar
     */
    public function getProductPrice();

    /**
     * Set ProductPrice.
     */
    public function setProductPrice($productPrice);

    /**
     * Get ProductQty.
     *
     * @return varchar
     */
    public function getProductQty();

    /**
     * Set ProductQty.
     */
    public function setProductQty($productQty);

    /**
     * Get WebsiteId.
     *
     * @return varchar
     */
    public function getWebsiteId();

    /**
     * Set WebsiteId.
     */
    public function setWebsiteId($websiteId);

    /**
     * Get StoreId.
     *
     * @return varchar
     */
    public function getStoreId();

    /**
     * Set StoreId.
     */
    public function setStoreId($storeId);

    /**
     * Get AddDate.
     *
     * @return varchar
     */
    public function getAddDate();

    /**
     * Set AddDate.
     */
    public function setAddDate($addDate);

    /**
     * Get SendDate.
     *
     * @return varchar
     */
    public function getSendDate();

    /**
     * Set SendDate.
     */
    public function setSendDate($sendDate);

    /**
     * Get SendCount.
     *
     * @return varchar
     */
    public function getSendCount();

    /**
     * Set SendCount.
     */
    public function setSendCount($sendCount);
    /**
     * Get Status.
     *
     * @return varchar
     */
    public function getStatus();

    /**
     * Set Status.
     */
    public function setStatus($status);

    /**
     * Get AlertType.
     *
     * @return varchar
     */
    public function getAlertType();

    /**
     * Set AlertType.
     */
    public function setAlertType($alertType);

    /**
     * Get LastArrivalContactDate.
     *
     * @return varchar
     */
    public function getLastArrivalContactDate();

    /**
     * Set LastArrivalContactDate.
     */
    public function setLastArrivalContactDate($lastArrivalContactDate);

    /**
     * Get TotalNumberRestock.
     *
     * @return varchar
     */
    public function getTotalNumberRestock();

    /**
     * Set TotalNumberRestock.
     */
    public function setTotalNumberRestock($totalNumberRestock);

    /**
     * Get RestockNotSent.
     *
     * @return varchar
     */
    public function getRestockNotSent();

    /**
     * Set RestockNotSent.
     */
    public function setRestockNotSent($restockNotSent);

    /**
     * Get RestockSent.
     *
     * @return varchar
     */
    public function getRestockSent();

    /**
     * Set RestockSent.
     */
    public function setRestockSent($restockSent);
}