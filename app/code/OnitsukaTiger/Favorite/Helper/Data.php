<?php

namespace OnitsukaTiger\Favorite\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Catalog\Model\ProductFactory;
use OnitsukaTiger\CustomCart\Model\ReservationOptions as ReservationFlag;
use Magento\CatalogInventory\Api\StockRegistryInterface;

class Data extends AbstractHelper
{
    public const ATTRIBUTES = 'attributes';
    public const SIMPLE_PRODUCT = 'simple_product';
    public const PRODUCT_TYPE_ID = 4;
    public const SIZE_CODE = 'size';
    public const CONFIGURABLE_CODE = Configurable::TYPE_CODE;

    public const PRODUCT_COL = [
        "name",
        "price",
        "thumbnail",
        "size"
    ];
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;
    /**
     * @var TimezoneInterface
     */
    protected $timezone;
    /**
     * @var ProductFactory
     */
    protected $product;
    /**
     * @var StockRegistryInterface;
     */
    protected $stockItemRepository;

    /**
     * @param Context $context
     * @param ResourceConnection $resourceConnection
     * @param TimezoneInterface $timezone
     * @param ProductFactory $product
     * @param StockRegistryInterface $stockItemRepository
     */
    public function __construct(
        Context $context,
        ResourceConnection $resourceConnection,
        TimezoneInterface $timezone,
        ProductFactory $product,
        StockRegistryInterface $stockItemRepository
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->timezone = $timezone;
        $this->product = $product;
        $this->_stockItemRepository = $stockItemRepository;
        parent::__construct($context);
    }

    /**
     * Get product attribute id
     *
     * @param $attributeCode
     * @param $typeId
     * @return mixed
     */
    public function getAttributeId($attributeCode, $typeId): mixed
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select();
        $select->from([$this->getTableName('eav_attribute')], ['*'])
            ->where('attribute_code = ?', $attributeCode)
            ->where('entity_type_id = ?', $typeId);
        $getAttributeResult = $connection->fetchAll($select);
        return $getAttributeResult[0]['attribute_id'];
    }

    /**
     * Get table name
     *
     * @param $table
     * @return string
     */
    public function getTableName($table): string
    {
        $connection = $this->resourceConnection->getConnection();
        return $connection->getTableName($table);
    }

    /**
     * Get formated date
     *
     * @param $date
     * @param $con
     * @return string
     * @throws \Exception
     */
    public function formatDate($date, $con): string
    {
        $time = $con == 'from' ? '00:00:00' : '23:59:59';
        $newDate = $this->timezone->date(new \DateTime($date))->format('Y-m-d');
        return $newDate . ' ' .$time;
    }

    /**
     * Get current date
     *
     * @return string
     */
    public function getCurrentDay(): string
    {
        return $this->timezone->date()->format('Y-m-d 23:59:59');
    }

    /**
     * Get select query
     *
     * @return \Magento\Framework\DB\Select
     */
    public function getConfigFavorites(): string
    {
        $ids = [];
        $idsStr = [];
        $sizeId = $this->getAttributeId(self::SIZE_CODE, self::PRODUCT_TYPE_ID);
        $wishlistItemOption = $this->getTableName('wishlist_item_option');
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select();
        $select->from(['wishlist_item_option' => $wishlistItemOption],
            ['id' => 'wishlist_item_option.wishlist_item_id'])
            ->where('wishlist_item_option.code = ?', self::ATTRIBUTES)
            ->where('wishlist_item_option.value NOT LIKE "%\"' . $sizeId . '\":%"');

        return $select;
    }

    /**
     * Get attribute array
     *
     * @param $attributeCodeArray
     * @return array
     */
    public function getAttributeIdsByAttributeCodes($attributeCodeArray): array
    {
        $result = [];
        if (count($attributeCodeArray) <= 0) {
            return $result;
        }

        $sqlCondition = join('","', $attributeCodeArray);
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select();
        $select->from(['ea' => $this->getTableName('eav_attribute')],
                ['attribute_code' => 'ea.attribute_code', 'attribute_id' => 'ea.attribute_id']
            )->where('ea.entity_type_id = ?', self::PRODUCT_TYPE_ID)
            ->where('ea.attribute_code IN ("'. $sqlCondition .'")',)
            ->order('ea.attribute_code ASC');

        $tmp = $connection->fetchAll($select);

        foreach ($tmp as $item) {
            $result[$item['attribute_code']] = $item['attribute_id'];
        }

        return $result;
    }

    /**
     * Get product preorder status
     *
     * @param $productId
     * @return bool
     */
    public function preOrderStatus($productId): bool
    {
        $product = $this->product->create()->load($productId);
        $reservationFlagTxt = $product->getAttributeText('reservation_flag');
        $reservationFlag = ($reservationFlagTxt == ReservationFlag::RESERVATION_PRE_ORDER) ? true : false;
        if ($reservationFlag) {
            date_default_timezone_set("Asia/Tokyo");
            $today = date('Y-m-d H:i:s');
            $from = $product->getReservationFrom() ?? date('Y-m-d');
            $from = date('Y-m-d H:i:s', strtotime($from));
            $to = $product->getReservationTo() ?? date('Y-m-d');
            $to = date('Y-m-d H:i:s', strtotime($to));
            if ($today >= $from && $today <= $to) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get presale date
     *
     * @param $productId
     * @return string
     */
    public function preSaleEstimatedShippingDate($productId): string
    {
        $product = $this->product->create()->load($productId);
        date_default_timezone_set("Asia/Tokyo");
        $estimatedShipping = $product->getEstimatedShipping() ?? false;
        $period = "";
        if (!$estimatedShipping) {
            return '';
        }
        switch ($value = date("d", strtotime($estimatedShipping))) {
            case (1 <= $value) && ($value <= 10):
                $period = __("Early");
                break;
            case (11 <= $value) && ($value <= 20):
                $period = __("Mid");
                break;
            case (21 <= $value) && ($value <= 31):
                $period = __("Late");
                break;
        }

        $finalDate = date('Y年n月', strtotime($estimatedShipping));
        return $finalDate. ' ' .$period;
    }

    /**
     * Get product stock object
     *
     * @param $productId
     * @return \Magento\CatalogInventory\Api\Data\StockItemInterface
     */
    public function getStockItem($productId): object
    {
        return $this->_stockItemRepository->getStockItem($productId);
    }

    /**
     * Get product by product id
     *
     * @param $productId
     * @return \Magento\Catalog\Model\Product
     */
    public function getProductById($productId): object
    {
        return $this->product->create()->load($productId);
    }
}
