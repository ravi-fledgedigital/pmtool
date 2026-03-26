<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Adminhtml\ReturnRules;

use Amasty\Rma\Controller\Adminhtml\AbstractReturnRules;
use Amasty\Rma\Controller\Adminhtml\RegistryConstants;
use Amasty\Rma\Api\ReturnRulesRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class Delete extends AbstractReturnRules
{
    /**
     * @var ReturnRulesRepositoryInterface
     */
    private $repository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Action\Context $context,
        ReturnRulesRepositoryInterface $repository,
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
        $id = (int)$this->getRequest()->getParam(RegistryConstants::RULE_ID);

        if ($id) {
            try {
                $this->repository->deleteById($id);
                $this->messageManager->addSuccessMessage(__('The rule has been deleted.'));

                return $this->resultRedirectFactory->create()->setPath('amrma/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Can\'t delete rule right now. Please review the log and try again.')
                );
                $this->logger->critical($e);
            }

            return $this->resultRedirectFactory->create()->setPath(
                'amrma/*/edit',
                [RegistryConstants::RULE_ID => $id]
            );
        } else {
            $this->messageManager->addErrorMessage(__('Can\'t find a rule to delete.'));
        }

        return $this->resultRedirectFactory->create()->setPath('amrma/*/');
    }
}
