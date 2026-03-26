<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-cataloglabel
 * @version   2.5.7
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);


namespace Mirasvit\CatalogLabel\Model\Label\Rule\Condition\Product;


use Magento\CatalogRule\Model\ResourceModel\Rule;
use Magento\Framework\Model\AbstractModel;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\CatalogLabel\Helper\ProductData;

class DiscountPercent extends AbstractProductCondition
{
    public function getCode(): string
    {
        return 'percent_discount';
    }

    public function getLabel(): string
    {
        return (string)__('Percent Discount');
    }

    public function getValueOptions(): ?array
    {
        return null;
    }

    public function getInputType(): string
    {
        return self::TYPE_STRING;
    }

    public function getValueElementType(): string
    {
        return self::TYPE_TEXT;
    }

    public function validate(AbstractModel $object, AbstractCondition $validator): bool
    {
        $this->productDataHelper->setProduct($object);

        $percent = $this->productDataHelper->getDiscountPercent();

        return $percent ? $validator->validateAttribute($percent) : false;
    }

    public function getExtraAttributesToSelect(): array
    {
        return [
            'price_view',
            'price',
            'special_price',
            'special_from_date',
            'special_to_date',
            'type_id'
        ];
    }
}
