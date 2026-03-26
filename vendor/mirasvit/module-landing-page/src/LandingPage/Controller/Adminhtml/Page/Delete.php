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

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Registry;
use Mirasvit\LandingPage\Api\Data\PageInterface;
use Mirasvit\LandingPage\Controller\Adminhtml\PageAbstract;
use Mirasvit\LandingPage\Repository\FilterRepository;
use Mirasvit\LandingPage\Repository\PageRepository;

class Delete extends PageAbstract
{
    private $filterRepository;

    public function __construct(
        FilterRepository $filterRepository,
        PageRepository   $pageRepository,
        Registry         $registry,
        ForwardFactory   $resultForwardFactory,
        Context          $context
    ) {
        $this->filterRepository = $filterRepository;
        parent::__construct($pageRepository, $registry, $resultForwardFactory, $context);
    }

    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($reviewId = $this->getRequest()->getParam(PageInterface::PAGE_ID)) {
            $model = $this->initModel();

            if (!$model->getId()) {
                $this->messageManager->addErrorMessage((string)__('This page no longer exists.'));

                return $resultRedirect->setPath('*/*/');
            }

            try {
                $filters = $this->filterRepository->getByPageId((int)$model->getId());
                foreach ($filters as $filter) {
                    $this->filterRepository->delete($filter);
                }

                $this->pageRepository->delete($model);
                $this->messageManager->addSuccessMessage((string)__('The page has been deleted.'));
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }

            return $resultRedirect->setPath('*/*/');
        }

        $this->messageManager->addErrorMessage((string)__('This page no longer exists.'));

        return $resultRedirect->setPath('*/*/');
    }
}
