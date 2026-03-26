<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Controller\Adminhtml\Template;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;

class Index extends \Magento\Backend\App\Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Amasty_PDFCustom::template';

    public function execute()
    {
        if ($this->getRequest()->getParam('ajax')) {
            return $this->resultFactory->create(ResultFactory::TYPE_FORWARD)->forward('grid');
        }

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Amasty_PDFCustom::template');
        $resultPage->getConfig()->getTitle()->prepend(__('PDF Templates'));
        $resultPage->addBreadcrumb(__('PDF Templates'), __('PDF Templates'));
        return $resultPage;
    }
}
