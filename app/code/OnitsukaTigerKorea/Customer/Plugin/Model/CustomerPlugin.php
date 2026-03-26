<?php

namespace OnitsukaTigerKorea\Customer\Plugin\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use OnitsukaTigerKorea\Customer\Helper\Data;
use Psr\Log\LoggerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;

class CustomerPlugin
{
    /**
     * @var Data
     */
    protected Data $helper;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var CartRepositoryInterface
     */
    protected CartRepositoryInterface $cartRepository;

    /**
     * @var QuoteCollectionFactory
     */
    protected QuoteCollectionFactory $quoteCollectionFactory;

    /**
     * @var ResourceConnection
     */
    protected ResourceConnection $resource;

    /**
     * @param LoggerInterface $logger
     * @param Data $helper
     * @param CartRepositoryInterface $cartRepository
     * @param ResourceConnection $resource
     * @param QuoteCollectionFactory $quoteCollectionFactory
     */
    public function __construct(
        LoggerInterface         $logger,
        Data                    $helper,
        CartRepositoryInterface $cartRepository,
        ResourceConnection      $resource,
        QuoteCollectionFactory  $quoteCollectionFactory
    )
    {
        $this->logger = $logger;
        $this->helper = $helper;
        $this->cartRepository = $cartRepository;
        $this->resource = $resource;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
    }

    /**
     * @param CustomerRepositoryInterface $subject
     * @param callable $deleteCustomerById Function we are wrapping around
     * @param int $customerId Input to the function
     * @return bool
     */
    public function aroundDeleteById(
        CustomerRepositoryInterface $subject,
        callable                    $deleteCustomerById,
        int                         $customerId
    ): bool
    {
        try {
            $customer = $subject->getById($customerId);
            $result = $deleteCustomerById($customerId);
            if ($this->helper->allowDeleteAccount($customer->getStoreId())) {
                $this->removeCartByCustomer($customerId);
                $this->removeCustomerLog($customerId);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }

        return $result;
    }

    /**
     * @param $customerId
     * @return void
     */
    private function removeCartByCustomer($customerId): void
    {
        $quoteCollection = $this->quoteCollectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId);
        if ($quoteCollection->getSize()) {
            foreach ( $quoteCollection as $item ) {
                $this->cartRepository->delete($item);
            }
        }
    }

    /**
     * @param $customerId
     * @return void
     */
    private function removeCustomerLog($customerId): void
    {
        $connection = $this->resource->getConnection();
        $condition = ['customer_id =?' => $customerId];
        $connection->delete('customer_log', $condition);
    }
}
