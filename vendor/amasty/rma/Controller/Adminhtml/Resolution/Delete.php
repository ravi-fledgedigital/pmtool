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
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class Delete extends AbstractResolution
{
    /**
     * @var ResolutionRepositoryInterface
     */
    private $repository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Action\Context $context,
        ResolutionRepositoryInterface $repository,
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
        $id = (int)$this->getRequest()->getParam(RegistryConstants::RESOLUTION_ID);

        if ($id) {
            try {
                $this->repository->deleteById($id);
                $this->messageManager->addSuccessMessage(__('The resolution has been deleted.'));

                return $this->resultRedirectFactory->create()->setPath('amrma/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Can\'t delete resolution right now. Please review the log and try again.')
                );
                $this->logger->critical($e);
            }

            return $this->resultRedirectFactory->create()->setPath(
                'amrma/*/edit',
                [RegistryConstants::RESOLUTION_ID => $id]
            );
        } else {
            $this->messageManager->addErrorMessage(__('Can\'t find a resolution to delete.'));
        }

        return $this->resultRedirectFactory->create()->setPath('amrma/*/');
    }
}
