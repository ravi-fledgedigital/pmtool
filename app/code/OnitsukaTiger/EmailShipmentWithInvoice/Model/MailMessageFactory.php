<?php

namespace OnitsukaTiger\EmailShipmentWithInvoice\Model;

use Magento\Framework\ObjectManagerInterface;

class MailMessageFactory
{
    protected $objectManager = null;

    /**
     * Instance name to create
     *
     * @var string
     */
    protected $instanceName = null;

    /**
     * Factory constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        $instanceName = \OnitsukaTiger\EmailShipmentWithInvoice\Model\MailMessage::class
    ) {
        $this->objectManager = $objectManager;
        $this->instanceName = $instanceName;
    }

    /**
     * Create class instance with specified parameters
     *
     * @param array $data
     * @return \OnitsukaTiger\EmailShipmentWithInvoice\Model\MailMessage
     */
    public function create(array $data = [])
    {
        return $this->objectManager->create($this->instanceName, $data);
    }
}
