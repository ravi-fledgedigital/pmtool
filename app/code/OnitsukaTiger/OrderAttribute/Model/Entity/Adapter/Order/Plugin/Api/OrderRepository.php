<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/
declare(strict_types=1);

namespace OnitsukaTiger\OrderAttribute\Model\Entity\Adapter\Order\Plugin\Api;

use OnitsukaTiger\OrderAttribute\Model\Entity\Adapter\Order\Adapter;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * For API. Extension Attributes Save Get
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class OrderRepository
{
    /**
     * @var Adapter
     */
    private $orderAdapter;

    public function __construct(Adapter $orderAdapter)
    {
        $this->orderAdapter = $orderAdapter;
    }

    /**
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $order
     * @throws LocalizedException
     * @return OrderInterface
     */
    public function afterGet(OrderRepositoryInterface $subject, OrderInterface $order): OrderInterface
    {
        $this->orderAdapter->addExtensionAttributesToOrder($order);

        return $order;
    }

    /**
     * @param OrderRepositoryInterface $subject
     * @param OrderSearchResultInterface $searchResult
     * @throws LocalizedException
     * @return OrderSearchResultInterface
     */
    public function afterGetList(
        OrderRepositoryInterface $subject,
        OrderSearchResultInterface $searchResult
    ): OrderSearchResultInterface {
        foreach ($searchResult->getItems() as $order) {
            $this->orderAdapter->addExtensionAttributesToOrder($order);
        }

        return $searchResult;
    }

    /**
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $order
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @return OrderInterface
     */
    public function afterSave(OrderRepositoryInterface $subject, OrderInterface $order): OrderInterface
    {
        $this->orderAdapter->saveOrderValues($order);
        $this->orderAdapter->addExtensionAttributesToOrder($order, true);

        return $order;
    }
}
