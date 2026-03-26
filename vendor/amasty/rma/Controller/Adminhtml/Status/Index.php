<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Adminhtml\Status;

use Amasty\Rma\Api\Data\StatusInterface;
use Amasty\Rma\Controller\Adminhtml\AbstractStatus;
use Amasty\Rma\Model\OptionSource\State;
use Amasty\Rma\Model\Status\OptionSource\AutoEvents;
use Amasty\Rma\Model\Status\ResourceModel\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;

class Index extends AbstractStatus
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var State
     */
    private $state;

    public function __construct(
        CollectionFactory $collectionFactory,
        State $state,
        Action\Context $context
    ) {
        parent::__construct($context);
        $this->collectionFactory = $collectionFactory;
        $this->state = $state;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->checkStatusSettings();

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Amasty_Rma::status');
        $resultPage->addBreadcrumb(__('RMA'), __('RMA'));
        $resultPage->addBreadcrumb(__('RMA Statuses'), __('RMA Statuses'));
        $resultPage->getConfig()->getTitle()->prepend(__('RMA Statuses'));

        return $resultPage;
    }

    public function checkStatusSettings()
    {
        $hasInitialStatus = $this->getStatusCollection()
            ->addFieldToFilter(StatusInterface::IS_INITIAL, 1)
            ->getSize();

        if (!$hasInitialStatus) {
            $this->messageManager->addWarningMessage(__('You don\'t have `Initial Status`. Please Create/Enable It.'));
        }

        foreach ($this->state->toArray() as $stateIndex => $stateName) {
            if (!$this->getStatusCollection()->addFieldToFilter(StatusInterface::STATE, $stateIndex)->getSize()) {
                $this->messageManager->addWarningMessage(__('State `%1` has no active statuses.', $stateName));
            }
        }

        $hasCancelStatus = $this->getStatusCollection()
            ->addFieldToFilter(StatusInterface::STATE, State::CANCELED)
            ->addFieldToFilter(StatusInterface::AUTO_EVENT, AutoEvents::CUSTOMER_CANCELED_RMA)
            ->getSize();
        if (!$hasCancelStatus) {
            $this->messageManager->addWarningMessage(__('Customer couldn\'t Cancel RMA because there is no'
                . ' active status in state `Cancel` with automatically set status on event `Customer Canceled RMA`.'
                . ' Please Create/Enable it.'));
        }
    }

    /**
     * @return \Amasty\Rma\Model\Status\ResourceModel\Collection
     */
    public function getStatusCollection()
    {
        $statusCollection = $this->collectionFactory->create();

        return $statusCollection->addNotDeletedFilter()
            ->addFieldToFilter(StatusInterface::IS_ENABLED, 1);
    }
}
