<?php

namespace OnitsukaTigerVn\Store\Observer;

use Magento\Framework\App\RequestInterface;
use OnitsukaTiger\Store\Helper\Data;
use Magento\Framework\UrlInterface;
use Magento\Framework\Controller\Result\ForwardFactory;

class FrontControllerActionPredispatch implements \Magento\Framework\Event\ObserverInterface
{
    const CUSTOMER_MODULE_NAME        = 'customer';
    const CHECKOUT_MODULE_NAME        = 'checkout';
    const CUSTOMER_CONTROLLER_NAME    = 'account';
    const CHECKOUT_CONTROLLER_NAME    = 'cart';
    const PATH_DISABLE_LOGIN_REGISTER = 'general_vn/customer/disable';
    const PATH_DISABLE_CHECKOUT       = 'general_vn/checkout/disable';

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var UrlInterface
     */
    protected $url;

    /**
     * @var ForwardFactory
     */
    protected $forwardFactory;

    /**
     * @param RequestInterface $request
     * @param Data $helper
     * @param UrlInterface $url
     * @param ForwardFactory $forwardFactory
     */
    public function __construct(
        RequestInterface $request,
        Data $helper,
        UrlInterface $url,
        ForwardFactory $forwardFactory
    ) {
        $this->request        = $request;
        $this->helper         = $helper;
        $this->url            = $url;
        $this->forwardFactory = $forwardFactory;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->isCustomerRedirect()) {
            $observer->getControllerAction()->getResponse()->setRedirect($this->url->getUrl('/'));
        } elseif ($this->isCheckoutRedirect()) {
            $resultForward = $this->forwardFactory->create();
            $resultForward->setController('index');
            $resultForward->forward('defaultNoRoute');
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isCustomerRedirect()
    {
        if ($this->helper->getConfigValue(self::PATH_DISABLE_LOGIN_REGISTER) &&
            ($this->request->getModuleName() == self::CUSTOMER_MODULE_NAME || $this->request->getControllerName() == self::CUSTOMER_CONTROLLER_NAME)
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isCheckoutRedirect()
    {
        if ($this->helper->getConfigValue(self::PATH_DISABLE_CHECKOUT) &&
            $this->request->getModuleName() == self::CHECKOUT_MODULE_NAME
        ) {
            return true;
        }

        return false;
    }
}
