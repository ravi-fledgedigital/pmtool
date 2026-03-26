<?php
/**
 * @author Aitoc Team
 * @copyright Copyright (c) 2022 Aitoc (https://www.aitoc.com)
 * @package Aitoc_SendGrid
 */


namespace Aitoc\SendGrid\Controller\Adminhtml\Singlesends;

use Magento\Backend\App\Action;

class Preview extends Action
{
    public function execute()
    {
        try {
            $this->_view->loadLayout();
            $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Single Send Preview'));
            $this->_view->renderLayout();
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred. The single send template can not be opened for preview.')
            );
            $this->_redirect('*/*/');
        }
    }
}
