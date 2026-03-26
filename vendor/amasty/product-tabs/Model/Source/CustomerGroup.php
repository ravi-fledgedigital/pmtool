<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Model\Source;

use Magento\Customer\Ui\Component\Listing\Column\Group\Options;

class CustomerGroup extends Options
{
    public const ALL = -1;

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        $options = parent::toOptionArray();
        $options[] = [
            'value' => self::ALL,
            'label' => __('All')
        ];

        return $options;
    }
}
