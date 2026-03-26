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
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class Delete extends AbstractStatus
{
    /**
     * @var StatusRepositoryInterface
     */
    private $repository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Action\Context $context,
        StatusRepositoryInterface $repository,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->repository = $repository;
        $this->logger = $logger;
    }

    /**
     * Delete action
     *
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function execute()
    {
        $id = (int)$this->getRequest()->getParam(RegistryConstants::STATUS_ID);

        if ($id) {
            try {
                $this->repository->deleteById($id);
                $this->messageManager->addSuccessMessage(__('The status has been deleted.'));

                return $this->resultRedirectFactory->create()->setPath('amrma/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Can\'t delete status right now. Please review the log and try again.')
                );
                $this->logger->critical($e);
            }

            return $this->resultRedirectFactory->create()->setPath(
                'amrma/*/edit',
                [RegistryConstants::STATUS_ID => $id]
            );
        } else {
            $this->messageManager->addErrorMessage(__('Can\'t find a status to delete.'));
        }

        return $this->resultRedirectFactory->create()->setPath('amrma/*/');
    }
}
