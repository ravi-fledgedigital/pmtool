<?php

namespace OnitsukaTigerKorea\Newsletter\Controller\Newsletter;

use Amasty\AdminActionsLog\Utils\EmailSender;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Newsletter\Model\SubscriberFactory;

class Remove extends Action
{

    /**
     * @var SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var EmailSender
     */
    protected $emailSender;

    /**
     * @var \Magento\Newsletter\Model\Subscriber
     */
    protected $subscriber;


    /**
     * Remove constructor.
     * @param Context $context
     * @param SubscriberFactory $subscriberFactory
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     */
    public function __construct(
        Context $context,
        SubscriberFactory $subscriberFactory,
        EmailSender $emailSender,
        \Magento\Newsletter\Model\Subscriber $subscriber
    )
    {
        $this->subscriber = $subscriber;
        $this->subscriberFactory = $subscriberFactory;
        $this->emailSender = $emailSender;
        $this->messageManager = $context->getMessageManager();
        parent::__construct($context);
    }

    /**
     * Unsubscribe newsletter.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $email = (string)$this->getRequest()->getParam('email');
        if ($email) {
            try {
                $checkSubscriber = $this->subscriber->loadByEmail(rawurldecode($email));
                if ($checkSubscriber->isSubscribed()) {
                    if ($checkSubscriber->getCustomerId()) {
                        $this->subscriberFactory->create()->unsubscribeCustomerById($checkSubscriber->getCustomerId());
                    } else {
                        $checkSubscriber->setStatus(3);
                        $checkSubscriber->save();
                        $checkSubscriber->sendUnsubscriptionEmail();
                    }
                    $this->messageManager->addSuccess(__('We have removed your newsletter subscription.'));
                }else {
                    $this->messageManager->addErrorMessage(__('You unsubscribed.'));
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e, $e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e, __('Something went wrong while unsubscribing you.'));
            }
        }
        /** @var \Magento\Framework\Controller\Result\Redirect $redirect */
        $redirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        $redirectUrl = $this->_redirect->getRedirectUrl();
        return $redirect->setUrl($redirectUrl);
    }
}
