<?php
namespace OnitsukaTiger\ImportExport\Model\Import;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;

class Product extends \Firebear\ImportExport\Model\Import\Product
{
    protected function _prepareRowForDb(array $rowData): array
    {
        $level = error_reporting();
        if (E_NOTICE & $level === 0) {
            return parent::_prepareRowForDb($rowData);
        }

        error_reporting($level & ~E_NOTICE);
        $rowData = parent::_prepareRowForDb($rowData);
        error_reporting($level);

        return $rowData;
    }

    /**
     * Product entity link field
     *
     * @var string
     */
    private $productEntityLinkField;

    /**
     * @param array $rowData
     * @return mixed|string
     * @throws \Exception
     */
    protected function getUrlKey($rowData): string
    {
        $url = \Magento\CatalogImportExport\Model\Import\Product::getUrlKey($rowData);
        if ($url === '') {
            $url = $this->generateUrlKey($rowData)[self::URL_KEY];
        }
        // we have deleted this line because formatUrlKey should use when url generated from name label etc
        //$url = $this->productUrl->formatUrlKey($url);
        return $url;
    }

    /**
     * @param array $rowData
     * @param null $storeIds
     * @return array
     * @throws Exception
     */
    protected function generateUrlKey(array $rowData, $storeIds = null)
    {
        $productEntityLinkField = $this->getProductEntityLinkField();
        $sku = $this->getCorrectSkuAsPerLength($rowData);
        $urlKey = $rowData[self::URL_KEY] ?? '';
        $name = $rowData[self::COL_NAME] ?? '';
        if ($this->isSkuExist($sku)) {
            $exiting = $this->getExistingSku($sku);
            if (!$urlKey) {
                $attr = $this->retrieveAttributeByCode(self::URL_KEY);
                $select = $this->getConnection()->select()
                    ->from($attr->getBackendTable(), ['value'])
                    ->where($productEntityLinkField . ' = (?)', $exiting['entity_id'])
                    ->where('attribute_id = (?)', $attr->getAttributeId());
                $urlKey = $this->getConnection()->fetchOne($select);
            }
            if (!$name) {
                $attr = $this->retrieveAttributeByCode(self::COL_NAME);
                $select = $this->getConnection()->select()
                    ->from($attr->getBackendTable(), ['value'])
                    ->where($productEntityLinkField . ' = (?)', $exiting['entity_id'])
                    ->where('attribute_id = (?)', $attr->getAttributeId());
                $name = $this->getConnection()->fetchOne($select);
                if (!$urlKey) {
                    $urlKey = $name;
                }
            }
        } else {
            $urlKey = isset($rowData[self::URL_KEY])
                ? $urlKey
                : $name;
        }
        if ($storeIds === null) {
            $storeIds = $this->getStoreIds();
        }

        // We should use formatUrlKey method only when we generate URL from name etc.
        $urlKey = ($urlKey != '') ? $urlKey : $this->productUrl->formatUrlKey($name);

        $isDuplicate = $this->isDuplicateUrlKey($urlKey, $sku, $storeIds);
        if ($isDuplicate || $this->urlKeyManager->isUrlKeyExist($sku, $urlKey)) {
            $urlKey = $this->productUrl->formatUrlKey(
                $name . '-' . $sku
            );
        }
        $rowData[self::URL_KEY] = $urlKey;
        $this->urlKeyManager->addUrlKeys($sku, $urlKey);
        return $rowData;
    }

    /**
     * Get product entity link field
     *
     * @return string
     * @throws Exception
     */
    protected function getProductEntityLinkField()
    {
        if (!$this->productEntityLinkField) {
            $this->productEntityLinkField = $this->getMetadataPool()
                ->getMetadata(ProductInterface::class)
                ->getLinkField();
        }
        return $this->productEntityLinkField;
    }

    /**
     * Check if product exists for specified SKU
     *
     * @param string $sku
     *
     * @return bool
     */
    protected function isSkuExist($sku)
    {
        if (version_compare($this->productMetadata->getVersion(), '2.2.0', '>=')) {
            $sku = strtolower($sku);
        }
        return isset($this->_oldSku[$sku]);
    }

    /**
     * Get existing product data for specified SKU
     *
     * @param string $sku
     *
     * @return array
     */
    protected function getExistingSku($sku)
    {
        if (version_compare($this->productMetadata->getVersion(), '2.2.0', '>=')) {
            $sku = strtolower($sku);
        }
        if (isset($this->_oldSku[$sku])) {
            $result = $this->_oldSku[$sku];
        } else {
            $result = false;
        }
        return $result;
    }
}