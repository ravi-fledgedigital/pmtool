<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Adminhtml\Resolution;

use Amasty\Rma\Controller\Adminhtml\AbstractResolution;
use Amasty\Rma\Controller\Adminhtml\RegistryConstants;
use Amasty\Rma\Api\ResolutionRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;

class Edit extends AbstractResolution
{
    /**
     * @var ResolutionRepositoryInterface
     */
    private $repository;

    public function __construct(
        ResolutionRepositoryInterface $repository,
        Action\Context $context
    ) {
        parent::__construct($context);
        $this->repository = $repository;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Amasty_Rma::resolution');

        if ($resolutionId = (int) $this->getRequest()->getParam(RegistryConstants::RESOLUTION_ID)) {
            try {
                $this->repository->getById($resolutionId);
                $resultPage->getConfig()->getTitle()->prepend(__('Edit Resolution'));
            } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
                $this->messageManager->addErrorMessage(__('This resolution no longer exists.'));

                return $this->resultRedirectFactory->create()->setPath('*/*/index');
            }
        } else {
            $resultPage->getConfig()->getTitle()->prepend(__('New Resolution'));
        }

        return $resultPage;
    }
}
