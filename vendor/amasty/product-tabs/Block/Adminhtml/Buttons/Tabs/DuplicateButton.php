<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Block\Adminhtml\Buttons\Tabs;

use Amasty\CustomTabs\Block\Adminhtml\Buttons\GenericButton;
use Amasty\CustomTabs\Api\Data\TabsInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class DuplicateButton
 */
class DuplicateButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        if (!$this->getTabId()) {
            return [];
        }

        $alertMessage = __('Are you sure you want to duplicate this?');
        $onClick = sprintf('deleteConfirm("%s", "%s")', $alertMessage, $this->getDuplicateUrl());

        $data = [
            'label' => __('Duplicate'),
            'class' => 'duplicate',
            'id' => 'edit-duplicate-button',
            'on_click' => $onClick,
            'sort_order' => 15,
        ];

        return $data;
    }

    /**
     * @return string
     */
    public function getDuplicateUrl()
    {
        return $this->getUrl('*/*/duplicate', [TabsInterface::TAB_ID => $this->getTabId()]);
    }

    /**
     * @return null|int
     */
    public function getTabId()
    {
        return (int)$this->request->getParam(TabsInterface::TAB_ID);
    }
}
