<?php
namespace Cpss\Crm\Controller\Account;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Controller\Result\Redirect;

class LoginFromApps extends Action implements CsrfAwareActionInterface, HttpPostActionInterface
{
    protected $_pageFactory;
    protected $session;
    protected $crmHelper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Cpss\Crm\Helper\Customer $crmHelper
    ) {
        $this->session = $customerSession;
        $this->crmHelper = $crmHelper;
        parent::__construct($context);
    }

    public function execute()
    {
        $request = $this->getRequest()->getParams();
        if (!$this->crmHelper->validateAppLoginCredentials($request)) {
            $this->session->setIsRedirectAppLogin(null);
            $this->session->setAppLoginRequest(null);
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }

        $this->session->setIsRedirectAppLogin(true);
        $this->session->setAppLoginRequest($request);

        return $this->_redirect('customer/account/login');
    }

    /**
     * createCsrfValidationException
     *
     * @return void
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setRefererOrBaseUrl();

        return new InvalidRequestException(
            $resultRedirect,
            [new Phrase('Invalid Form Key. Please refresh the page.')]
        );
    }

    /**
     * validateForCsrf
     *
     * @param  mixed $request
     * @return bool
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
