<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Attribute\Data;

use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Entity\Entity;
use Magento\Eav\Model\Attribute\Data\Select;

class SelectPlugin
{

    /**
     * @param Select $subject
     * @param Select $result
     * @param array|string $value
     * @return Select
     */
    public function afterCompactValue(Select $subject, Select $result, $value)
    {
        $attribute = $subject->getAttribute();

        // Works only on admin edit attributes page, when checkboxes have unchecked all inputs (value = false)
        if (($attribute->getEntityType()->getEntityTypeCode() === Entity::ENTITY_TYPE_CODE)
            && ($attribute->getFrontendInput() === 'checkboxes')
            && $value === false
        ) {
            $subject->getEntity()->setData($attribute->getAttributeCode(), '');
        }

        return $result;
    }
}
