<?php

namespace OnitsukaTigerKorea\ActionLog\Ui\Component\Listing\Column;

use \Amasty\AdminActionsLog\Model\VisitHistoryEntry\VisitHistoryEntryFactory;
use \Magento\Framework\View\Element\UiComponent\ContextInterface;
use \Magento\Framework\View\Element\UiComponentFactory;
use \Magento\Ui\Component\Listing\Columns\Column;
use \Magento\Framework\Api\SearchCriteriaBuilder;

class Username extends Column
{
    protected $_orderRepository;
    protected $_searchCriteria;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        protected VisitHistoryEntryFactory $visitHistoryEntryFactory,
        SearchCriteriaBuilder $criteria,
        array $components = [],
        array $data = []
    ) {
        $this->_searchCriteria  = $criteria;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $visitEntry  = $this->visitHistoryEntryFactory->create()->load($item['visit_id']);
                $item[$this->getData('name')] = $visitEntry->getUsername();
            }
        }

        return $dataSource;
    }
}
