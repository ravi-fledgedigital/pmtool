<?php
namespace Seoulwebdesign\Kakaopay\Block;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Seoulwebdesign\Base\Helper\Data as BaseHelper;
use Seoulwebdesign\Base\Helper\MobileDetect;
use Seoulwebdesign\Kakaopay\Helper\Constant;
use Seoulwebdesign\Kakaopay\Model\OrderProcessing;

class Pay extends \Magento\Framework\View\Element\Template
{
    /** @var Session  */
    protected $checkoutSession;
    /** @var  */
    protected $order;
    /** @var MobileDetect  */
    protected $mobileDetect;
    /** @var RequestInterface  */
    protected $request;
    /** @var OrderProcessing */
    protected $orderProcessing;
    /** @var BaseHelper  */
    protected $baseHelper;

    /**
     * @param Context $context
     * @param Session $checkoutSession
     * @param MobileDetect $mobileDetect
     * @param RequestInterface $request
     * @param OrderProcessing $orderProcessing
     * @param BaseHelper $baseHelper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Session $checkoutSession,
        MobileDetect $mobileDetect,
        RequestInterface $request,
        OrderProcessing $orderProcessing,
        BaseHelper $baseHelper,
        array $data = []
    ) {
        $this->baseHelper = $baseHelper;
        $this->mobileDetect = $mobileDetect;
        $this->request = $request;
        $this->checkoutSession = $checkoutSession;
        $this->orderProcessing = $orderProcessing;
        parent::__construct($context, $data);
    }

    public function getOrder()
    {
        $oid = $this->request->getParam('oid');
        if ($oid) {
            $this->order = $this->orderProcessing->getOrderByIncrementId($oid);
        }
        return $this->order;
    }

    /**
     * @return mixed
     */
    public function getRepayUrl()
    {
        $this->order = $this->getOrder();
        $payUrl = $this->baseHelper->getUrl(
            Constant::KAKAOPAY_RETRY_URL,
            ['oid'=>$this->order->getIncrementId()]
        );
        return $payUrl;
    }

    /**
     * @return string
     */
    public function getCartUrl()
    {
        return $this->getUrl(
            'checkout/cart',
            ['_secure' => $this->getRequest()->isSecure()]
        );
    }
}
