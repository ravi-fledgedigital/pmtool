<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Adminhtml\Reason;

use Amasty\Rma\Controller\Adminhtml\AbstractReason;
use Amasty\Rma\Controller\Adminhtml\RegistryConstants;
use Amasty\Rma\Api\ReasonRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;

class Edit extends AbstractReason
{
    /**
     * @var ReasonRepositoryInterface
     */
    private $repository;

    public function __construct(
        ReasonRepositoryInterface $repository,
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
        $resultPage->setActiveMenu('Amasty_Rma::reason');

        if ($reasonId = (int) $this->getRequest()->getParam(RegistryConstants::REASON_ID)) {
            try {
                $this->repository->getById($reasonId);
                $resultPage->getConfig()->getTitle()->prepend(__('Edit Reason'));
            } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
                $this->messageManager->addErrorMessage(__('This reason no longer exists.'));

                return $this->resultRedirectFactory->create()->setPath('*/*/index');
            }
        } else {
            $resultPage->getConfig()->getTitle()->prepend(__('New Reason'));
        }

        return $resultPage;
    }
}
