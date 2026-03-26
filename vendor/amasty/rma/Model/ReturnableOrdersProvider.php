<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model;

use Amasty\Rma\Controller\RegistryConstants;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

class ReturnableOrdersProvider
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        ConfigProvider $configProvider,
        CollectionFactory $orderCollectionFactory,
        Registry $registry,
        ?StoreManagerInterface $storeManager = null
    ) {
        $this->configProvider = $configProvider;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->registry = $registry;
        $this->storeManager = $storeManager ?? ObjectManager::getInstance()->get(StoreManagerInterface::class);
    }

    public function getOrders(int $customerId): array
    {
        $statuses = $this->configProvider->getAllowedOrderStatuses();
        $orderCollection = $this->orderCollectionFactory->create();
        $orderCollection->addFieldToSelect(
            [
                OrderInterface::ENTITY_ID,
                OrderInterface::INCREMENT_ID,
                OrderInterface::CREATED_AT,
                OrderInterface::GRAND_TOTAL
            ]
        )->setOrder(OrderInterface::ENTITY_ID);
        if ($customerId !== 0) {
            $orderCollection->addFieldToFilter(
                OrderInterface::CUSTOMER_ID,
                $customerId
            );
        } else {
            $orderCollection->join(
                'sales_order_address',
                'main_table.entity_id = sales_order_address.parent_id'
                . ' and sales_order_address.address_type = \'billing\'',
                []
            )->addFieldToFilter(
                OrderInterface::CUSTOMER_EMAIL,
                $this->registry->registry(RegistryConstants::GUEST_DATA)['email']
            )->addFieldToFilter(
                'sales_order_address.' . OrderAddressInterface::LASTNAME,
                $this->registry->registry(RegistryConstants::GUEST_DATA)['lastname']
            );
            if ($this->configProvider->isRmaRestrictedByStore()) {
                $orderCollection->addFieldToFilter(
                    'main_table.' . OrderInterface::STORE_ID,
                    $this->storeManager->getStore()->getId()
                );
            }
        }

        if ($statuses) {
            $orderCollection->addFieldToFilter(OrderInterface::STATUS, $statuses);
        }

        return (array)$orderCollection->getData();
    }
}
