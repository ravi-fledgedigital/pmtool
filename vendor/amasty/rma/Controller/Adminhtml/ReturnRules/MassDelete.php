<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Adminhtml\ReturnRules;

use Amasty\Rma\Controller\Adminhtml\AbstractReturnRules;
use Amasty\Rma\Api\ReturnRulesRepositoryInterface;
use Amasty\Rma\Model\ReturnRules\ResourceModel\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Psr\Log\LoggerInterface;

class MassDelete extends AbstractReturnRules
{
    /**
     * @var ReturnRulesRepositoryInterface
     */
    private $repository;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Action\Context $context,
        ReturnRulesRepositoryInterface $repository,
        CollectionFactory $collectionFactory,
        Filter $filter,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->repository = $repository;
        $this->collectionFactory = $collectionFactory;
        $this->filter = $filter;
        $this->logger = $logger;
    }

    /**
     * Mass action execution
     *
     * @throws LocalizedException
     */
    public function execute()
    {
        $this->filter->applySelectionOnTargetProvider();

        /** @var \Amasty\Rma\Model\ReturnRules\ResourceModel\Collection $collection */
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $deletedRules = 0;
        $failedRules = 0;

        if ($collection->count()) {
            foreach ($collection->getItems() as $rule) {
                try {
                    $this->repository->delete($rule);
                    $deletedRules++;
                } catch (LocalizedException $e) {
                    $failedRules++;
                } catch (\Exception $e) {
                    $this->logger->error(
                        __('Error occurred while deleting rule with ID %1. Error: %2'),
                        [$rule->getId(), $e->getMessage()]
                    );
                }
            }
        }

        if ($deletedRules !== 0) {
            $this->messageManager->addSuccessMessage(
                __('%1 rule(s) has been successfully deleted', $deletedRules)
            );
        }

        if ($failedRules !== 0) {
            $this->messageManager->addErrorMessage(
                __('%1 rule(s) has been failed to delete', $failedRules)
            );
        }

        return $this->resultRedirectFactory->create()->setRefererUrl();
    }
}
