<?php

namespace OnitsukaTigerKorea\OrderCancel\Controller\Adminhtml\Reason;

use OnitsukaTigerKorea\OrderCancel\Controller\Adminhtml\AbstractReason;
use OnitsukaTigerKorea\OrderCancel\Api\ReasonRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class Delete extends AbstractReason
{
    /**
     * @var ReasonRepositoryInterface
     */
    private ReasonRepositoryInterface $repository;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param Action\Context $context
     * @param ReasonRepositoryInterface $repository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Action\Context $context,
        ReasonRepositoryInterface $repository,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->repository = $repository;
        $this->logger = $logger;
    }

    /**
     * Delete action
     */
    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('reason_id');

        if ($id) {
            try {
                $this->repository->deleteById($id);
                $this->messageManager->addSuccessMessage(__('The reason has been deleted.'));

                return $this->resultRedirectFactory->create()->setPath('ordercancel/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Can\'t delete reason right now. Please review the log and try again.')
                );
                $this->logger->critical($e);
            }

            return $this->resultRedirectFactory->create()->setPath(
                'ordercancel/*/edit',
                ['reason_id' => $id]
            );
        } else {
            $this->messageManager->addErrorMessage(__('Can\'t find a reason to delete.'));
        }

        return $this->resultRedirectFactory->create()->setPath('ordercancel/*/');
    }
}
