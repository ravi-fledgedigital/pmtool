<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Block\Adminhtml\Buttons\Status;

use Amasty\Rma\Block\Adminhtml\Buttons\GenericButton;
use Amasty\Rma\Controller\Adminhtml\RegistryConstants;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        if (!$this->getStatusId()) {
            return [];
        }
        $alertMessage = __('Are you sure you want to do this?');
        $onClick = sprintf('deleteConfirm("%s", "%s")', $alertMessage, $this->getDeleteUrl());

        $data = [
            'label' => __('Delete'),
            'class' => 'delete',
            'id' => 'condition-edit-delete-button',
            'on_click' => $onClick,
            'sort_order' => 20,
        ];

        return $data;
    }

    /**
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl('*/*/delete', [RegistryConstants::STATUS_ID => $this->getStatusId()]);
    }

    /**
     * @return null|int
     */
    public function getStatusId()
    {
        return (int)$this->request->getParam(RegistryConstants::STATUS_ID);
    }
}
