<?php
namespace OnitsukaTiger\Catalog\Model\Import\Product;

use Magento\Catalog\Model\Product\Attribute\Backend\Sku;
use Magento\CatalogImportExport\Model\Import\Product;
use Magento\CatalogImportExport\Model\Import\Product\RowValidatorInterface;

class Validator extends \Magento\CatalogImportExport\Model\Import\Product\Validator
{
    /**
     * Text validation
     *
     * @param mixed $attrCode
     * @param string $type
     * @return bool
     */
    protected function textValidation($attrCode, $type)
    {
        $val = $this->string->cleanString($this->_rowData[$attrCode]);
        if ($type == 'text') {
            $valid = $this->string->strlen($val) < Product::DB_MAX_TEXT_LENGTH;
        } else if ($attrCode == Product::COL_SKU) {
            $valid = $this->string->strlen($val) <= SKU::SKU_MAX_LENGTH;
        } else if ($attrCode == 'image' || $attrCode == 'thumbnail') {
            $valid = true;
        } else {
            $valid = $this->string->strlen($val) < Product::DB_MAX_VARCHAR_LENGTH;
        }
        if (!$valid) {
            $this->_addMessages([RowValidatorInterface::ERROR_EXCEEDED_MAX_LENGTH]);
        }
        return $valid;
    }
}
