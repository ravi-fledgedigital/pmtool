<?php


namespace OnitsukaTiger\NetSuite\Model\Handler;


class Retry // extends \Thread
{
    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    protected $publisher;
    /**
     * @var \OnitsukaTiger\NetSuite\Api\Queue\ProductMessageInterface
     */
    protected $message;

    public function __construct(
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \OnitsukaTiger\NetSuite\Api\Queue\ProductMessageInterface $message
    )
    {
        $this->publisher = $publisher;
        $this->message = $message;
    }

    public function run()
    {
        sleep(60);
        $this->publisher->publish('onitsukatiger.netsuite.product', $this->message);
    }
}
