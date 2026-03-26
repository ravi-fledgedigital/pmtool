<?php
namespace OnitsukaTiger\NetSuite\Model;

use OnitsukaTiger\NetSuite\Api\Data\PriceUpdateRequestInterface;
use Magento\Framework\DataObject;

class PriceUpdateRequest extends DataObject implements PriceUpdateRequestInterface
{
    public function getItems()
    {
        return $this->getData('items');
    }

    public function setItems(array $items)
    {
        return $this->setData('items', $items);
    }
}