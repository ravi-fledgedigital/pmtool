<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Request\Email;

use Amasty\Rma\Api\ConditionRepositoryInterface;
use Amasty\Rma\Api\ReasonRepositoryInterface;
use Amasty\Rma\Api\ResolutionRepositoryInterface;
use Amasty\Rma\Model\OptionSource\ItemStatus;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Framework\DataObject;

class EmailRequestItem extends DataObject
{
    public const ITEM_SKU = 'item_sku';
    public const CONDITION = 'condition';
    public const REASON = 'reason';
    public const RESOLUTION = 'resolution';
    public const QTY = 'qty';
    public const STATUS = 'status';

    /**
     * @var ConditionRepositoryInterface
     */
    private $conditionRepository;

    /**
     * @var ReasonRepositoryInterface
     */
    private $reasonRepository;

    /**
     * @var ResolutionRepositoryInterface
     */
    private $resolutionRepository;

    /**
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * @var ItemStatus
     */
    private $itemStatus;

    public function __construct(
        ConditionRepositoryInterface $conditionRepository,
        ReasonRepositoryInterface $reasonRepository,
        ResolutionRepositoryInterface $resolutionRepository,
        OrderItemRepositoryInterface $orderItemRepository,
        ItemStatus $itemStatus,
        array $data = []
    ) {
        parent::__construct($data);
        $this->conditionRepository = $conditionRepository;
        $this->reasonRepository = $reasonRepository;
        $this->resolutionRepository = $resolutionRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->itemStatus = $itemStatus;
    }

    /**
     * @param \Amasty\Rma\Model\Request\RequestItem $requestItem
     * @param int|null $storeId
     *
     * @return EmailRequestItem
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function parseRequestItem($requestItem, $storeId = null)
    {
        return $this->setItemSku($this->orderItemRepository->get($requestItem->getOrderItemId())->getSku())
            ->setCondition($this->conditionRepository->getById($requestItem->getConditionId(), $storeId)
                ->getStore()->getLabel())
            ->setReason($this->reasonRepository->getById($requestItem->getReasonId(), $storeId)
                ->getStore()->getLabel())
            ->setResolution($this->resolutionRepository->getById($requestItem->getResolutionId(), $storeId)
                ->getStore()->getLabel())
            ->setQty($requestItem->getQty())
            ->setStatus($this->itemStatus->toArray()[$requestItem->getItemStatus()]);
    }

    /**
     * @param string $sku
     *
     * @return EmailRequestItem
     */
    public function setItemSku($sku)
    {
        return $this->setData(self::ITEM_SKU, $sku);
    }

    /**
     * @return string
     */
    public function getItemSku()
    {
        return $this->_getData(self::ITEM_SKU);
    }

    /**
     * @param string $condition
     *
     * @return EmailRequestItem
     */
    public function setCondition($condition)
    {
        return $this->setData(self::CONDITION, $condition);
    }

    /**
     * @return string
     */
    public function getCondition()
    {
        return $this->_getData(self::CONDITION);
    }

    /**
     * @param string $reason
     *
     * @return EmailRequestItem
     */
    public function setReason($reason)
    {
        return $this->setData(self::REASON, $reason);
    }

    /**
     * @return string
     */
    public function getReason()
    {
        return $this->_getData(self::REASON);
    }

    /**
     * @param string $resolution
     *
     * @return EmailRequestItem
     */
    public function setResolution($resolution)
    {
        return $this->setData(self::RESOLUTION, $resolution);
    }

    /**
     * @return string
     */
    public function getResolution()
    {
        return $this->_getData(self::RESOLUTION);
    }

    /**
     * @param string $qty
     *
     * @return EmailRequestItem
     */
    public function setQty($qty)
    {
        return $this->setData(self::QTY, $qty);
    }

    /**
     * @return string
     */
    public function getQty()
    {
        return $this->_getData(self::QTY);
    }

    /**
     * @param string $status
     *
     * @return EmailRequestItem
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->_getData(self::STATUS);
    }
}
