<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Block\Adminhtml\Buttons\Request;

use Amasty\Rma\Block\Adminhtml\Buttons\GenericButton;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class HistoryButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        $data = [
            'label' => __('History'),
            'class' => 'action- scalable',
            'id' => 'amrma-history-button',
            'on_click' => 'require("uiRegistry").get("amrma_request_form.amrma_request_form.modal").toggleModal();'
        ];

        return $data;
    }
}
