<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace OnitsukaTiger\EmailTemplate\Block\Adminhtml\Template\Grid\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\Action as BackendAction;
/**
 * Email templates grid block action item renderer
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Action extends \Magento\Email\Block\Adminhtml\Template\Grid\Renderer\Action
{
    /**
     * Render grid column
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $actions = [];

        $actions[] = [
            'url' => $this->getUrl('adminhtml/*/preview', ['id' => $row->getId()]),
            'popup' => true,
            'target' => '_blank',
            'label' => __('Preview'),
            'caption' => __('Preview'),
        ];

        $this->getColumn()->setActions($actions);

        return BackendAction::render($row);
    }
}
