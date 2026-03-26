<?php
namespace OnitsukaTiger\NetSuite\Api;

interface ProductPriceUpdateInterface
{

    /**
     * Update prices for products across websites
     *
     * @param \OnitsukaTiger\NetSuite\Api\Data\PriceUpdateItemInterface[] $items
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Validation\ValidationException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function execute(array $items);
}