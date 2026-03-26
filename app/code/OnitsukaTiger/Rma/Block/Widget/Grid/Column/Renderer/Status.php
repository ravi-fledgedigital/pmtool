<?php

namespace OnitsukaTiger\Rma\Block\Widget\Grid\Column\Renderer;

use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\Data\StatusInterface;
use Magento\Framework\DataObject;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Backend\Block\Context;
use Amasty\Rma\Model\Status\Repository;
use Amasty\Rma\Model\Status\ResourceModel\CollectionFactory as StatusCollectionFactory;

class Status extends AbstractRenderer
{
    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var array
     */
    public $statusColor;

    public function __construct(
        Context $context,
        Repository $repository,
        StatusCollectionFactory $statusCollectionFactory,
        array   $data = [])
    {
        $this->repository = $repository;


        $statusCollection = $statusCollectionFactory->create();
        $statusCollection->addFieldToSelect(StatusInterface::STATUS_ID)
            ->addFieldToSelect(StatusInterface::COLOR);
        foreach ($statusCollection->getData() as $status) {
            $this->statusColor[$status[StatusInterface::STATUS_ID]] = $status[StatusInterface::COLOR];
        }

        parent::__construct($context, $data);
    }

    /**
     * @param DataObject $row
     * @return mixed|string|void
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        $result = [];

        $statusCollection = $this->repository->getEmptyStatusCollection()
            ->addFieldToFilter(StatusInterface::IS_ENABLED, 1)
            ->addNotDeletedFilter()
            ->addFieldToSelect([StatusInterface::STATUS_ID, StatusInterface::TITLE])
            ->setOrder(StatusInterface::PRIORITY, \Magento\Framework\Data\Collection::SORT_ORDER_ASC);

        foreach ($statusCollection->getData() as $status) {
            $result[$status[StatusInterface::STATUS_ID]] = $status[StatusInterface::TITLE];
        }

        $statusColor = $this->statusColor[$row->getData(RequestInterface::STATUS)];

        $html = '<div class="data-grid-cell-content amrma-status" style="background-color:'.$statusColor.'">'. $this->escapeHtml( $result[$value] ).'</div>';
        return $html;
    }
}
