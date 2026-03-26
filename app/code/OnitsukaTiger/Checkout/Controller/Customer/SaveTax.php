<?php

namespace OnitsukaTiger\Checkout\Controller\Customer;

use Exception;
use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class SaveTax extends Action implements HttpPostActionInterface
{
    /**
     * @var Customer
     */
    private $customerResource;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * Reorder constructor.
     * @param Context $context
     * @param Customer $customerResource
     * @param Session $customerSession
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        Customer $customerResource,
        Session $customerSession,
        LoggerInterface $logger
    ) {
        $this->customerResource = $customerResource;
        $this->customerSession = $customerSession;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Save Taxvat
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            $customerId = (int) $this->getRequest()->getParam('customer_id');
            $taxId = $this->getRequest()->getParam('tax_id');
            $this->validateRequest($customerId, $taxId);
            $entityTable = $this->customerResource->getEntityTable();
            $connection = $this->customerResource->getConnection();

            $connection->update(
                $entityTable,
                ['taxvat' => $taxId],
                ['entity_id = ?' => $customerId]
            );

            $result->setData(['success' => true]);
        } catch (LocalizedException $e) {
            $this->logger->critical($e->getMessage(), ['Exception' => $e]);
            $result->setData(['success' => false, 'error' => $e->getMessage()]);
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage(), ['Exception' => $e]);
            $result->setData(['success' => false, 'error' => $e->getMessage()]);
        }

        return $result;
    }

    /**
     * @throws Exception|LocalizedException
     */
    private function validateRequest($customerId, $taxId)
    {
        if (!$taxId || !$customerId) {
            throw new LocalizedException(__('Empty input value.'));
        }
        if ($customerId != $this->customerSession->getCustomerId()) {
            throw new Exception('Wrong Customer!');
        }
    }
}
