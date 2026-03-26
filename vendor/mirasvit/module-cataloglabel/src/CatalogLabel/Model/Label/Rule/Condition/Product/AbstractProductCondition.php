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




namespace Mirasvit\CatalogLabel\Model\Label\Rule\Condition\Product;


use Magento\Framework\Model\AbstractModel;
use Magento\Rule\Model\Condition\AbstractCondition;
use Mirasvit\CatalogLabel\Helper\ProductData;

abstract class AbstractProductCondition
{
    const TYPE_SELECT      = 'select';
    const TYPE_MULTISELECT = 'multiselect';
    const TYPE_BOOL        = 'boolean';
    const TYPE_STRING      = 'string';
    const TYPE_TEXT        = 'text'; // element value type only

    protected $productDataHelper;

    public function __construct(ProductData $productDataHelper)
    {
        $this->productDataHelper = $productDataHelper;
    }

    abstract public function getCode(): string;

    abstract public function getLabel(): string;

    abstract public function getValueOptions(): ?array;

    abstract public function getInputType(): string;

    abstract public function getValueElementType(): string;

    abstract public function validate(AbstractModel $object, AbstractCondition $validator): bool;

    public function getExtraAttributesToSelect(): array
    {
        return [];
    }
}
