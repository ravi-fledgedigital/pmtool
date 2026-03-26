<?php
/**
 * @author Aitoc Team
 * @copyright Copyright (c) 2022 Aitoc (https://www.aitoc.com)
 * @package Aitoc_Smtp
 */


namespace Aitoc\Smtp\Controller\Adminhtml\Log;

class Index extends \Aitoc\Smtp\Controller\Adminhtml\Log
{
    /**
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->initPage();
        $resultPage->getConfig()->getTitle()->prepend(__('Emails Log'));

        return $resultPage;
    }
}
