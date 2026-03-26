<?php

namespace Seoulwebdesign\Kakaopay\Controller\Checkout;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Payment\Gateway\Command\CommandManagerPoolInterface;
use Psy\Exception\ThrowUpException;
use Seoulwebdesign\Base\Helper\Data as BaseHelper;
use Seoulwebdesign\Base\Helper\MobileDetect;
use Seoulwebdesign\Kakaopay\Helper\Constant;
use Seoulwebdesign\Kakaopay\Logger\Logger;
use Seoulwebdesign\Kakaopay\Model\OrderProcessing;
use Seoulwebdesign\Kakaopay\Model\Ui\ConfigProvider;

class Retry implements HttpGetActionInterface
{
    protected $resultPageFactory;
    /** @var Logger  */
    protected $logger;
    /** @var RequestInterface  */
    protected $request;
    /** @var ManagerInterface  */
    protected $messageManager;
    /** @var Context  */
    private $context;
    /** @var OrderProcessing  */
    private $orderProcessing;
    /** @var CommandManagerPoolInterface  */
    private $commandManagerPool;
    /** @var MobileDetect  */
    private $mobileDetect;
    /** @var BaseHelper  */
    private  $baseHelper;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param RequestInterface $request
     * @param OrderProcessing $orderProcessing
     * @param Logger $logger
     */
    public function __construct(
        Context          $context,
        PageFactory      $resultPageFactory,
        RequestInterface $request,
        OrderProcessing $orderProcessing,
        CommandManagerPoolInterface $commandManagerPool,
        MobileDetect $mobileDetect,
        BaseHelper $baseHelper,
        Logger           $logger
    ) {
        $this->context = $context;
        $this->resultPageFactory = $resultPageFactory;
        $this->request = $request;
        $this->logger = $logger;
        $this->orderProcessing = $orderProcessing;
        $this->commandManagerPool = $commandManagerPool;
        $this->mobileDetect = $mobileDetect;
        $this->baseHelper = $baseHelper;
        $this->messageManager = $context->getMessageManager();
    }
    public function execute()
    {
        $orderId  = $this->request->getParam('oid');
        $order = $this->orderProcessing->getOrderByIncrementId($orderId);
        $commandExecutor = $this->commandManagerPool->get(ConfigProvider::CODE);
        try {
            $commandExecutor->executeByCode('initialize', $order->getPayment());

            $order = $this->orderProcessing->getOrderByIncrementId($orderId);
            if ($this->mobileDetect->isMobile()) {
                $payUrl = $order->getPayment()->getAdditionalInformation(Constant::KAKAOPAY_MOBILE_RESPONSE_URL);
            } else {
                $payUrl = $order->getPayment()->getAdditionalInformation(Constant::KAKAOPAY_RESPONSE_URL);
            }
            $resultRedirect = $this->context->getResultRedirectFactory()->create();
            $resultRedirect->setUrl((string)$payUrl);
            return $resultRedirect;
        } catch (ThrowUpException $t) {
            $this->messageManager->addErrorMessage($t->getMessage());
            $resultRedirect = $this->context->getResultRedirectFactory()->create();
            $failUrl = $this->baseHelper->getUrl(Constant::KAKAOPAY_FAIL_URL, ['oid'=> $orderId]);
            $resultRedirect->setUrl($failUrl);
            return $resultRedirect;
        }
    }
}
