<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\PlatformNetsuite\Model\Customer\Address;

/**
 * Class UpdatePublisher
 * @package Firebear\PlatformNetsuite\Model\Customer\Address
 */
class UpdatePublisher
{
    const TOPIC_NAME = 'netsuite.customer.address.update';

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    private $publisher;

    /**
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     */
    public function __construct(\Magento\Framework\MessageQueue\PublisherInterface $publisher)
    {
        $this->publisher = $publisher;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(\Magento\Customer\Api\Data\AddressInterface $address)
    {
        $this->publisher->publish(self::TOPIC_NAME, $address);
    }
}
