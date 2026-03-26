<?php


namespace Seoulwebdesign\Kakaopay\Controller\Checkout;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Seoulwebdesign\Kakaopay\Logger\Logger;

/**
 * Class Pay
 *
 * @package Seoulwebdesign\Cnspay\Controller\Order
 */
class Fail implements CsrfAwareActionInterface, HttpGetActionInterface
{

    protected $resultPageFactory;
    /** @var Logger  */
    protected $logger;
    /** @var RequestInterface  */
    protected $request;
    /** @var ManagerInterface  */
    protected $messageManager;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param RequestInterface $request
     * @param Logger $logger
     */
    public function __construct(
        Context          $context,
        PageFactory      $resultPageFactory,
        RequestInterface $request,
        Logger           $logger
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->request = $request;
        $this->logger = $logger;
        $this->messageManager = $context->getMessageManager();
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $params = $this->request->getParams();
        $this->logger->debug('Fail' . print_r($params, true));
        $this->messageManager->addErrorMessage(
            __("Something went wrong. Your payment was rejected. Please try again!")
        );
        return $this->resultPageFactory->create();
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}

