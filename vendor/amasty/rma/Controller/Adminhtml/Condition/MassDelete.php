<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Adminhtml\Condition;

use Amasty\Rma\Controller\Adminhtml\AbstractCondition;
use Amasty\Rma\Api\ConditionRepositoryInterface;
use Amasty\Rma\Model\Condition\ResourceModel\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Psr\Log\LoggerInterface;

class MassDelete extends AbstractCondition
{
    /**
     * @var ConditionRepositoryInterface
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
        ConditionRepositoryInterface $repository,
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

        /** @var \Amasty\Rma\Model\Condition\ResourceModel\Collection $collection */
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $deletedConditions = 0;
        $failedConditions = 0;

        if ($collection->count()) {
            foreach ($collection->getItems() as $condition) {
                try {
                    $this->repository->delete($condition);
                    $deletedConditions++;
                } catch (LocalizedException $e) {
                    $failedConditions++;
                } catch (\Exception $e) {
                    $this->logger->error(
                        __('Error occurred while deleting condition with ID %1. Error: %2'),
                        [$condition->getConditionId(), $e->getMessage()]
                    );
                }
            }
        }

        if ($deletedConditions !== 0) {
            $this->messageManager->addSuccessMessage(
                __('%1 condition(s) has been successfully deleted', $deletedConditions)
            );
        }

        if ($failedConditions !== 0) {
            $this->messageManager->addErrorMessage(
                __('%1 condition(s) has been failed to delete', $failedConditions)
            );
        }

        return $this->resultRedirectFactory->create()->setRefererUrl();
    }
}
