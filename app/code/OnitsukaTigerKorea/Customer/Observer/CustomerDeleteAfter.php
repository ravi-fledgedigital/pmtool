<?php

namespace OnitsukaTigerKorea\Customer\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\ResourceConnection;

class CustomerDeleteAfter implements ObserverInterface
{
    /**
     * @var customerFactory
     */
    protected $customerFactory;
    /**
     * @var resourceConnection
     */
    protected $resourceConnection;

    /**
     * @param customerFactory
     * @param resourceConnection
     */

    public function __construct
    (
        CustomerFactory $customerFactory,
        ResourceConnection $resourceConnection
    )
    {
        $this->customerFactory = $customerFactory;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get CustomerId and Delete Table
     */
    public function execute(Observer $observer)
    {
        $customer = $observer->getData('customer');
        // Get customer id
        $customerId = $customer->getId();

        try {
            $connection = $this->resourceConnection->getConnection();

            $tableName = $connection->getTableName('amasty_amrules_cache_queue');

            $condition = ['customer_id = ?' => $customerId];
            //Delete id
            $connection->delete($tableName, $condition);

        } catch(\Exception $e) {
            echo "id not found";
        }

    }
}
