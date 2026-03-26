<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-landing-page
 * @version   1.1.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\LandingPage\Controller\Adminhtml\Page;

use Magento\Framework\Controller\ResultFactory;
use Mirasvit\LandingPage\Api\Data\PageInterface;
use Mirasvit\LandingPage\Controller\Adminhtml\PageAbstract;

class Edit extends PageAbstract
{
    public function execute()
    {
        $model = $this->initModel();
        $id    = (int)$this->getRequest()->getParam(PageInterface::PAGE_ID);

        if ($id && !$model) {
            $this->messageManager->addErrorMessage((string)__('This Page no longer exists.'));
            $resultRedirect = $this->resultRedirectFactory->create();

            return $resultRedirect->setPath('*/*/');
        }

        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $this->initPage($resultPage)
            ->getConfig()->getTitle()->prepend(
                $model->getId()
                    ? (string)__('Landing Page "%1"', $model->getName())
                    : (string)__('New Landing Page')
            );

        return $resultPage;
    }
}
