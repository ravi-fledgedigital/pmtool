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

namespace Mirasvit\Scroll\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ModeSource implements OptionSourceInterface
{
    const MODE_INFINITE          = 'infinite';
    const MODE_BUTTON            = 'button';
    const MODE_INFINITE_BUTTON   = 'infinite_button';
    const MODE_BUTTON_INFINITE   = 'button_infinite';
    const MODE_BUTTON_DEFAULT    = 'button_default';

    public function toOptionArray(): array
    {
        return [
            ['value' => 0, 'label' => __('Disabled')],
            ['value' => self::MODE_INFINITE, 'label' => __('Infinite Scroll')],
            ['value' => self::MODE_BUTTON, 'label' => __('Load More Button')],
            ['value' => self::MODE_INFINITE_BUTTON, 'label' => __('Infinite Scroll + Load More Button')],
            ['value' => self::MODE_BUTTON_INFINITE, 'label' => __('Load More Button + Infinite Scroll')],
            ['value' => self::MODE_BUTTON_DEFAULT, 'label' => __('Load More Button + Default Pagination')]
        ];
    }
}
