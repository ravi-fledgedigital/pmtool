<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Admin Actions Log for Magento 2
 */

namespace Amasty\AdminActionsLog\Block\Adminhtml\System\Config\Form;

use Amasty\Base\Block\Adminhtml\System\Config\Form\Field\Promo\DynamicFields;

class ExcludeLogging extends DynamicFields
{
    /**
     * @var bool
     */
    protected $_addAfter = false;

    protected function _prepareToRender(): void
    {
        $this->addColumn('flag', [
            'label' => __('Flag Name'),
        ]);
        parent::_prepareToRender();
    }
}
