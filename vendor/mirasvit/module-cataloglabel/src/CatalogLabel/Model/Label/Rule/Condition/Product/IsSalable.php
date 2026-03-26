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


use Magento\Framework\Model\AbstractModel;
use Magento\Rule\Model\Condition\AbstractCondition;

class IsSalable extends AbstractProductCondition
{

    public function getCode(): string
    {
        return 'is_salable';
    }

    public function getLabel(): string
    {
        return (string)__('Is Salable');
    }

    public function getValueOptions(): ?array
    {
        return [
            ['value' => 0, 'label' => (string)__('No')],
            ['value' => 1, 'label' => (string)__('Yes')],
        ];
    }

    public function getInputType(): string
    {
        return self::TYPE_SELECT;
    }

    public function getValueElementType(): string
    {
        return self::TYPE_SELECT;
    }

    public function validate(AbstractModel $object, AbstractCondition $validator): bool
    {
        $object = $object->load($object->getId());
        $validator->setValueParsed((bool)$validator->getValueParsed());

        return $validator->validateAttribute((bool)$object->getIsSalable());
    }
}
