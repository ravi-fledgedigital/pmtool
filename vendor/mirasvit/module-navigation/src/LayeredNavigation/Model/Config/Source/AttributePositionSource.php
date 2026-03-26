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
 * @package   mirasvit/module-navigation
 * @version   2.9.34
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\LayeredNavigation\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Mirasvit\LayeredNavigation\Api\Data\AttributeConfigInterface;

class AttributePositionSource implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => AttributeConfigInterface::POSITION_SIDEBAR,
                'label' => __('Sidebar (default)'),
            ],
            [
                'value' => AttributeConfigInterface::POSITION_HORIZONTAL,
                'label' => __('Horizontal'),
            ],
            [
                'value' => AttributeConfigInterface::POSITION_BOTH,
                'label' => __('Both'),
            ],
        ];
    }
}
