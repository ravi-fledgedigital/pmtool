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


use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Rule\Model\Condition\AbstractCondition;

class Category extends AbstractProductCondition
{

    public function getCode(): string
    {
        return 'category_ids';
    }

    public function getLabel(): string
    {
        return (string)__('Category');
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

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function validate(AbstractModel $object, AbstractCondition $validator): bool
    {
        $op = $validator->getOperatorForValidate();

        $catIds = $object->getAvailableInCategories();

        if (!is_array($catIds)) {
            $catIds = [];
        }

        // getAvailableInCategories() returns ids only for visible products
        $catIds = array_unique(array_merge($catIds, $object->getCategoryIds()));

        if (($op == '==') || ($op == '!=')) {
            /** @var ProductInterface $object */
            if (is_array($catIds)) {
                $value = $validator->getValueParsed();
                $value = preg_split('#\s*[,;]\s*#', (string)$value, 0, PREG_SPLIT_NO_EMPTY);

                $findElemInArray = array_intersect($catIds, $value);

                if (count($findElemInArray) > 0) {
                    if ($op == '==') {
                        $result = true;
                    }
                    if ($op == '!=') {
                        $result = false;
                    }
                } else {
                    if ($op == '==') {
                        $result = false;
                    }
                    if ($op == '!=') {
                        $result = true;
                    }
                }

                return $result;
            }
        } else {
            return $validator->validateAttribute($catIds);
        }
    }
}
