<?php

namespace OnitsukaTigerKorea\OrderCancel\Controller\Adminhtml\Reason;

use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Exception\NoSuchEntityException;
use OnitsukaTigerKorea\OrderCancel\Controller\Adminhtml\AbstractReason;
use OnitsukaTigerKorea\OrderCancel\Api\ReasonRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;

class Edit extends AbstractReason
{
    /**
     * @var ReasonRepositoryInterface
     */
    private ReasonRepositoryInterface $repository;

    /**
     * @param ReasonRepositoryInterface $repository
     * @param Action\Context $context
     */
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
        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('OnitsukaTigerKorea_OrderCancel::reason');

        if ($reasonId = (int) $this->getRequest()->getParam('reason_id')) {
            try {
                $this->repository->getById($reasonId);
                $resultPage->getConfig()->getTitle()->prepend(__('Edit Cancel Reason'));
            } catch (NoSuchEntityException $exception) {
                $this->messageManager->addErrorMessage(__('This Order Cancel reason no longer exists.'));

                return $this->resultRedirectFactory->create()->setPath('*/*/index');
            }
        } else {
            $resultPage->getConfig()->getTitle()->prepend(__('New Order Cancel Reason'));
        }

        return $resultPage;
    }
}
