<?php

namespace OnitsukaTigerIndo\RmaAccount\Controller\Index;

use Amasty\Rma\Model\Request\ResourceModel\CollectionFactory as RmaCollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use OnitsukaTigerIndo\RmaAccount\Block\RmaAccount;

class Index extends Action
{
    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var Http
     */
    protected $redirect;

    /**
     * @var PageFactory
     */
    private PageFactory $pageFactory;

    /**
     * @var RmaAccount
     */
    private RmaAccount $rmaAccount;

    /**
     * @var RmaCollectionFactory
     */
    private RmaCollectionFactory $rmaCollectionFactory;

    /**
     * @var OrderCollectionFactory
     */
    private OrderCollectionFactory $orderCollectionFactory;

    /**
     * Index constructor.
     *
     * @param PageFactory $pageFactory
     * @param Context $context
     * @param Session $customerSession
     * @param Http $redirect
     * @param StoreManagerInterface $storeManager
     * @param RmaAccount $rmaAccount
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param RmaCollectionFactory $rmaCollectionFactory
     */
    public function __construct(
        PageFactory $pageFactory,
        Context $context,
        Session $customerSession,
        Http $redirect,
        StoreManagerInterface $storeManager,
        RmaAccount $rmaAccount,
        OrderCollectionFactory $orderCollectionFactory,
        RmaCollectionFactory   $rmaCollectionFactory,
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->redirect = $redirect;
        $this->pageFactory = $pageFactory;
        $this->_storeManager=$storeManager;
        $this->rmaAccount = $rmaAccount;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->rmaCollectionFactory = $rmaCollectionFactory;
    }

    /**
     * Index Method
     *
     * @return Http|HttpInterface|ResponseInterface|ResultInterface|Page
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        if (!$this->customerSession->isLoggedIn()) {
            $url = $this->_storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_WEB,
                false
            );
            $storeUrl = $url . 'customer/account/login/';
            return $this->redirect->setRedirect($storeUrl);
        }

        $orderId = $this->getRequest()->getParam('order_id');
        if (!$orderId) {
            return $this->redirect->setRedirect('noroute');
        }
        $customerId = $this->rmaAccount->getCustomerId();
        $orderCollection = $this->orderCollectionFactory->create();
        $orderCollection->addFieldToFilter('increment_id', $orderId)
            ->addFieldToFilter('customer_id', $customerId);

        if ($orderCollection->getSize() <= 0) {
            return $this->redirect->setRedirect('noroute');
        }

        $orderEntityId = $orderCollection->getFirstItem()->getEntityId();
        $requestCollection = $this->rmaCollectionFactory->create();
        $requestCollection->addFieldToFilter('order_id', $orderEntityId)
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('status', 4);
        if ($requestCollection->getSize() <= 0) {
            return $this->redirect->setRedirect('noroute');
        }

        return $this->pageFactory->create();
    }
}
