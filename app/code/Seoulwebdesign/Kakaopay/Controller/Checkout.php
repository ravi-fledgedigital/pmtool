<?php

namespace Seoulwebdesign\Kakaopay\Controller;

use Magento\Checkout\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Payment\Gateway\Command\CommandManagerPoolInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Seoulwebdesign\Base\Helper\MobileDetect;
use Seoulwebdesign\Kakaopay\Helper\ConfigHelper;
use Seoulwebdesign\Kakaopay\Logger\Logger;
use Seoulwebdesign\Kakaopay\Model\OrderProcessing;

abstract class Checkout implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var Session
     */
    protected $checkoutSession;
    protected $resultJsonFactory;
    protected $orderFactory;
    protected $jsonFactory;
    protected $configHelper;
    protected $storeManager;
    protected $baseUrl;
    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    public $logger;
    /**
     * @var OrderProcessing
     */
    protected $orderProcessing;
    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @var CommandManagerPoolInterface
     */
    protected $commandManagerPool;

    protected $mobileDetect;
    protected $resultRedirectFactory;


    /**
     * @param Context $context
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param OrderFactory $orderFactory
     * @param Session $checkoutSession
     * @param Data $checkoutData
     * @param JsonFactory $resultJsonFactory
     * @param OrderSender $orderSender
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     * @param ConfigHelper $configHelper
     * @param CartRepositoryInterface $quoteRepository
     * @param OrderProcessing $orderProcessing
     * @param CommandManagerPoolInterface $commandManagerPool
     * @param MobileDetect $mobileDetect
     * @param ManagerInterface $messageManager
     * @param ResultFactory $resultFactory
     * @param RequestInterface $request
     * @param array $params
     * @throws NoSuchEntityException
     */
    public function __construct(
        Context $context,
        \Magento\Payment\Helper\Data $paymentHelper,
        OrderFactory $orderFactory,
        Session $checkoutSession,
        Data $checkoutData,
        JsonFactory $resultJsonFactory,
        OrderSender $orderSender,
        StoreManagerInterface $storeManager,
        Logger $logger,
        ConfigHelper $configHelper,
        CartRepositoryInterface $quoteRepository,
        OrderProcessing $orderProcessing,
        CommandManagerPoolInterface $commandManagerPool,
        MobileDetect $mobileDetect,
        protected ManagerInterface        $messageManager,
        protected ResultFactory  $resultFactory,
        protected RequestInterface $request,
        $params = []
    ) {
        $this->configHelper = $configHelper;
        $this->logger = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->orderSender = $orderSender;
        $this->jsonFactory = $resultJsonFactory;
        $this->storeManager = $storeManager;
        $this->quoteRepository = $quoteRepository;
        $this->orderProcessing = $orderProcessing;
        $this->commandManagerPool = $commandManagerPool;
        $this->mobileDetect = $mobileDetect;
        $this->resultRedirectFactory = $resultFactory;
        $this->baseUrl = $this->storeManager->getStore()->getBaseUrl();
    }

    /**
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @param $action
     * @param string|null $controller
     * @param string|null $module
     * @param array|null $params
     * @return void
     */
    protected function forward($action, ?string $controller = null, ?string $module = null, ?array $params = null)
    {
        $request = $this->request;

        $request->initForward();

        if (isset($params)) {
            $request->setParams($params);
        }

        if (isset($controller)) {
            $request->setControllerName($controller);

            // Module should only be reset if controller has been specified
            if (isset($module)) {
                $request->setModuleName($module);
            }
        }

        $request->setActionName($action);
        $request->setDispatched(false);
    }

    /**
     * @param string $url
     * @return mixed
     */
    protected function _redirect(string $url)
    {
        $resultRedirect = $this->resultRedirectFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath($url);
    }
    protected function getRequest()
    {
        return $this->request;
    }
}
