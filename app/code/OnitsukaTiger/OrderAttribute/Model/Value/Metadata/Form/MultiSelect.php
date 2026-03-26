<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Value\Metadata\Form;

class MultiSelect extends \Magento\Eav\Model\Attribute\Data\Multiselect
{
    /**
     * @inheritdoc
     */
    public function compactValue($value)
    {
        if ($value === false) {
            $value = '';
        }

        return parent::compactValue($value);
    }
}
