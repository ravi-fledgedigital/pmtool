<?php

namespace OnitsukaTigerKorea\ActionLog\Plugin;

class AddDataToPageViewGrid
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * AddDataToOrdersGrid constructor.
     *
     * @param \Psr\Log\LoggerInterface $customLogger
     * @param array $data
     */
    public function __construct(
        \Psr\Log\LoggerInterface $customLogger,
        array $data = []
    ) {
        $this->logger   = $customLogger;
    }

    /**
     * @param \Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory $subject
     * @param \Magento\Sales\Model\ResourceModel\Order\Grid\Collection $collection
     * @param $requestName
     * @return mixed
     */
    public function afterGetReport($subject, $collection, $requestName)
    {
        if ($requestName !== 'pageviewhistory_listing_data_source') {
            return $collection;
        }
        if ($collection->getMainTable() === $collection->getResource()->getTable('amasty_audit_visit_details')) {
            try {
                $amastyAuditVisitEntryTableName = $collection->getResource()->getTable('amasty_audit_visit_entry');
                $collection->getSelect()->joinLeft(
                    ['amave' => $amastyAuditVisitEntryTableName],
                    'amave.id = main_table.visit_id',
                    ['username', 'session_start', 'session_end']
                );
            } catch (\Zend_Db_Select_Exception $selectException) {
                $this->logger->log(100, $selectException);
            }
        }

        return $collection;
    }
}
