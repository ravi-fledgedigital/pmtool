<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Controller\Adminhtml\Tabs;

use Amasty\CustomTabs\Controller\Adminhtml\Tabs;

/**
 * Class Index
 */
class Create extends Tabs
{
    /**
     * @return void
     */
    public function execute()
    {
        $this->_forward('edit');
    }
}
