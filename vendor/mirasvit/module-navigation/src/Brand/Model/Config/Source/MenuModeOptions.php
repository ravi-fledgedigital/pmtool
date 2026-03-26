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

namespace Mirasvit\Brand\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class MenuModeOptions implements OptionSourceInterface
{
    const MODE_LINK  = 0;
    const MODE_MODAL = 1;

    public function toOptionArray(): array
    {
        return [
            ['value' => self::MODE_LINK, 'label' => __('Link')],
            ['value' => self::MODE_MODAL, 'label' => __('Modal')],
        ];
    }
}
