<?php

namespace OnitsukaTigerVn\Checkout\Controller\Checkout;

use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\View\LayoutFactory;
use Magento\Quote\Model\QuoteRepository;

class saveInQuote extends Action
{
    protected $resultForwardFactory;
    protected $layoutFactory;
    protected $cart;
    private Session $checkoutSession;
    private QuoteRepository $quoteRepository;

    public function __construct(
        Context $context,
        ForwardFactory $resultForwardFactory,
        LayoutFactory $layoutFactory,
        Cart $cart,
        Session $checkoutSession,
        QuoteRepository $quoteRepository,
    ) {
        parent::__construct($context);
        $this->resultForwardFactory = $resultForwardFactory;
        $this->layoutFactory = $layoutFactory;
        $this->cart = $cart;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
    }

    public function execute()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/aaaaaaa.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $checkVal = $this->getRequest()->getParam('checkVal');
        $termsAndConditionAgree = $this->getRequest()->getParam('termscheckVal');
        $logger->info("checkval : " . $checkVal);
        $logger->info("==============");
        $logger->info("termsAndConditionAgree : " . $termsAndConditionAgree);
        $quoteId = $this->checkoutSession->getQuoteId();
        $quote = $this->quoteRepository->get($quoteId);
        $quote->setVatInvoiceAgree($checkVal);
        $quote->setTermsAndConditionAgree($termsAndConditionAgree);
        $quote->save();
    }
}
