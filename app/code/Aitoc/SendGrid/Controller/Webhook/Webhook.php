<?php
/**
 * @author Aitoc Team
 * @copyright Copyright (c) 2022 Aitoc (https://www.aitoc.com)
 * @package Aitoc_SendGrid
 */


namespace Aitoc\SendGrid\Controller\Webhook;

use Magento\Backend\App\Action;
use Magento\Newsletter\Model\Subscriber;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

class Webhook extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    /**
     * @var \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory
     */
    private $subscriberCollectionFactory;

    public function __construct(
        Action\Context $context,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollectionFactory
    ) {
        parent::__construct($context);
        $this->subscriberCollectionFactory = $subscriberCollectionFactory;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }
    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    public function execute()
    {
        $data = file_get_contents("php://input");
        $events = json_decode($data, true);

        foreach ($events as $event) {
            if ($event['event'] == 'unsubscribe') {
                $subscriber = $this->subscriberCollectionFactory->create()
                    ->getItemByColumnValue('subscriber_email', $event['email']);
                $subscriber->setStatus(Subscriber::STATUS_UNSUBSCRIBED);
                $subscriber->save();
            }
        }
    }

    /**
     * Check url keys. If non valid - redirect
     *
     * @return bool
     *
     * @see \Magento\Backend\App\Request\BackendValidator for default
     * request validation.
     */
    public function _processUrlKeys()
    {
        return true;
    }
}