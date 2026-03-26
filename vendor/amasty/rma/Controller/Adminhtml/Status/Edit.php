<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Adminhtml\Status;

use Amasty\Rma\Controller\Adminhtml\AbstractStatus;
use Amasty\Rma\Controller\Adminhtml\RegistryConstants;
use Amasty\Rma\Api\StatusRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;

class Edit extends AbstractStatus
{
    /**
     * @var StatusRepositoryInterface
     */
    private $repository;

    public function __construct(
        StatusRepositoryInterface $repository,
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
        $resultPage->setActiveMenu('Amasty_Rma::status');

        if ($statusId = (int)$this->getRequest()->getParam(RegistryConstants::STATUS_ID)) {
            try {
                $this->repository->getById($statusId);
                $resultPage->getConfig()->getTitle()->prepend(__('Edit Status'));
            } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
                $this->messageManager->addErrorMessage(__('This status no longer exists.'));

                return $this->resultRedirectFactory->create()->setPath('*/*/index');
            }
        } else {
            $resultPage->getConfig()->getTitle()->prepend(__('New Status'));
        }

        return $resultPage;
    }
}
