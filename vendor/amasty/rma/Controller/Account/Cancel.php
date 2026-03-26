<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Account;

use Amasty\Rma\Api\CustomerRequestRepositoryInterface;
use Amasty\Rma\Model\ConfigProvider;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;

class Cancel extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var CustomerRequestRepositoryInterface
     */
    private $customerRequestRepository;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        Session $customerSession,
        CustomerRequestRepositoryInterface $customerRequestRepository,
        ConfigProvider $configProvider,
        Context $context
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->customerRequestRepository = $customerRequestRepository;
        $this->configProvider = $configProvider;
    }

    public function execute()
    {
        if (($requestId = (int)$this->getRequest()->getParam('request'))
            && ($customerId = $this->customerSession->getCustomerId())
        ) {
            if ($this->customerRequestRepository->closeRequest($requestId, $customerId)) {
                $this->messageManager->addSuccessMessage(__('Return Request successfully closed.'));

                return $this->resultRedirectFactory->create()->setPath(
                    $this->configProvider->getUrlPrefix() . '/account/view',
                    ['request' => $requestId]
                );
            }
        }

        return $this->resultRedirectFactory->create()->setPath(
            $this->configProvider->getUrlPrefix() . '/account/history'
        );
    }
}
