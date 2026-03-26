<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Block\Adminhtml\Attribute\Edit\Tab;

use Magento\Eav\Block\Adminhtml\Attribute\Edit\Options\AbstractOptions;

class Options extends AbstractOptions
{
    protected function _prepareLayout()
    {
        $this->addChild('labels', 'Magento\Eav\Block\Adminhtml\Attribute\Edit\Options\Labels');
        $this->addChild('tooltip', 'OnitsukaTiger\OrderAttribute\Block\Adminhtml\Attribute\Edit\Tab\Options\Tooltip');
        $this->addChild('options', 'OnitsukaTiger\OrderAttribute\Block\Adminhtml\Attribute\Edit\Tab\Options\Options');

        return $this;
    }
}
