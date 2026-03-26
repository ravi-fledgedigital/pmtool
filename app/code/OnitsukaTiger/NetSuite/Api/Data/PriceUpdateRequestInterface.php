<?php
namespace OnitsukaTiger\NetSuite\Api\Data;

interface PriceUpdateRequestInterface
{
    /**
     * @return \OnitsukaTiger\NetSuite\Api\Data\PriceItemInterface[]
     */
    public function getItems();

    /**
     * @param \OnitsukaTiger\NetSuite\Api\Data\PriceItemInterface[] $items
     * @return void
     */
    public function setItems(array $items);
}