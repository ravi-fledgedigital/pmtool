<?php
namespace OnitsukaTiger\NetSuite\Model;

use Magento\Framework\Exception\InputException;
use Magento\Framework\MessageQueue\PublisherInterface;
use OnitsukaTiger\NetSuite\Api\ProductPriceUpdateInterface;

class ProductPriceUpdate implements ProductPriceUpdateInterface
{
    /**
     * @var PublisherInterface
     */
    protected $publisher;

    /**
     * @param PublisherInterface $publisher
     */
    public function __construct(
        PublisherInterface $publisher
    ) {
        $this->publisher = $publisher;
    }

    public function execute(array $items)
    {
        if (empty($items)) {
            throw new InputException(__('Invalid request. "items" must be a non-empty array.'));
        }

        $this->publisher->publish('onitsukatiger.price.update.queue', $items);

        return ['message' => 'Products price update request sent to queue'];
    }

}