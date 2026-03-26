<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Email;

use Amasty\Rma\Api\RequestRepositoryInterface;
use Amasty\Rma\Controller\RegistryConstants;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Psr\Log\LoggerInterface;

class Login implements ActionInterface
{
    /**
     * @var RedirectFactory
     */
    private $redirectFactory;

    /**
     * @var CustomerSession
     */
    private $session;

    /**
     * @var RequestRepositoryInterface
     */
    private $requestRepository;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        RedirectFactory $redirectFactory,
        CustomerSession $customerSession,
        RequestInterface $request,
        RequestRepositoryInterface $requestRepository,
        CustomerRegistry $customerRegistry,
        LoggerInterface $logger
    ) {
        $this->redirectFactory = $redirectFactory;
        $this->session = $customerSession;
        $this->request = $request;
        $this->requestRepository = $requestRepository;
        $this->customerRegistry = $customerRegistry;
        $this->logger = $logger;
    }

    public function execute()
    {
        $redirect = $this->redirectFactory->create();

        try {
            $hash = $this->request->getParam(RegistryConstants::HASH_PARAM, '');
            $returnRequest = $this->requestRepository->getByHash($hash);
            $customer = $this->customerRegistry->retrieve($returnRequest->getCustomerId());

            if ($this->session->isLoggedIn() && $returnRequest->getCustomerId() != $this->session->getCustomerId()) {
                $this->session->logout();
                $redirect->setPath('customer/account/login');
            } else {
                $this->session->setCustomerAsLoggedIn($customer);
                $redirect->setRefererOrBaseUrl();
            }
        } catch (\Throwable $e) {
            $this->logger->error(__('RMA: Unable to autologin a customer. Error is: %1', $e->getMessage()));
            $redirect->setPath('customer/account/login');
        }

        return $redirect;
    }
}
