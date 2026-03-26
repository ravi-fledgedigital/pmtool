<?php
/** phpcs:ignoreFile */

namespace OnitsukaTiger\Rma\Controller\Account;

use Amasty\Rma\Api\CustomerRequestRepositoryInterface;
use Amasty\Rma\Model\ConfigProvider;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator;

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

    /**
     * @var Validator
     */
    private $formKeyValidator;

    /**
     * @param Session $customerSession
     * @param CustomerRequestRepositoryInterface $customerRequestRepository
     * @param ConfigProvider $configProvider
     * @param Context $context
     * @param Validator $formKeyValidator
     */
    public function __construct(
        Session                            $customerSession,
        CustomerRequestRepositoryInterface $customerRequestRepository,
        ConfigProvider                     $configProvider,
        Context                            $context,
        Validator                          $formKeyValidator,
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->customerRequestRepository = $customerRequestRepository;
        $this->configProvider = $configProvider;
        $this->formKeyValidator = $formKeyValidator;
    }

    public function execute()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/rma_cancel.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("====== Cancel Controller Execute =======");

        $request = $this->getRequest();

        if (!$this->formKeyValidator->validate($request)) {
            $logger->info("Invalid form submission detected.");
            $this->messageManager->addErrorMessage(__('Invalid form submission.'));
            return $this->resultRedirectFactory->create()->setPath(
                $this->configProvider->getUrlPrefix() . '/account/history'
            );
        }

        $requestId = (int)$request->getParam('request_id');
        $customerId = $this->customerSession->getCustomerId();

        if (!$requestId || !$customerId) {
            $logger->info("Invalid request: Missing request ID or customer ID.");
            $this->messageManager->addErrorMessage(__('Invalid request.'));
            return $this->resultRedirectFactory->create()->setPath(
                $this->configProvider->getUrlPrefix() . '/account/history'
            );
        }

        try {
            if ($this->customerRequestRepository->getById($requestId, $customerId)) {
                $logger->info("Inside Get By Id Request: " . $requestId);
                $logger->info("Inside Get By Id Customer: " . $customerId);
                if ($this->customerRequestRepository->closeRequest($requestId, $customerId)) {
                    $logger->info("Inside closeRequest request id : " . $requestId);
                    $logger->info("Inside closeRequest customer id : " . $customerId);
                    $this->messageManager->addSuccessMessage(__('Return Request successfully closed.'));

                    return $this->resultRedirectFactory->create()->setPath(
                        $this->configProvider->getUrlPrefix() . '/account/view',
                        ['request' => $requestId]
                    );
                }
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $logger->info("Inside catch");
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->resultRedirectFactory->create()->setPath(
                $this->configProvider->getUrlPrefix() . '/account/view',
                ['request' => $requestId]
            );
        }
        return $this->resultRedirectFactory->create()->setPath(
            $this->configProvider->getUrlPrefix() . '/account/history'
        );
    }
}
