<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Guest;

use Amasty\Rma\Api\CustomerRequestRepositoryInterface;
use Amasty\Rma\Controller\RegistryConstants;
use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Model\Cookie\HashChecker;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Magento\Sales\Api\OrderRepositoryInterface;

class History extends \Magento\Framework\App\Action\Action
{
    /**
     * @var HashChecker
     */
    private $hashChecker;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var CustomerRequestRepositoryInterface
     */
    private $customerRequestRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        Registry $registry,
        CustomerRequestRepositoryInterface $customerRequestRepository,
        HashChecker $hashChecker,
        ConfigProvider $configProvider,
        OrderRepositoryInterface $orderRepository,
        Context $context
    ) {
        parent::__construct($context);
        $this->hashChecker = $hashChecker;
        $this->registry = $registry;
        $this->customerRequestRepository = $customerRequestRepository;
        $this->orderRepository = $orderRepository;
        $this->configProvider = $configProvider;
    }

    public function execute()
    {
        if ($hash = $this->hashChecker->getHash()) {
            try {
                $request = $this->customerRequestRepository->getByHash($hash);
                $order = $this->orderRepository->get($request->getOrderId());
                $this->registry->register(
                    RegistryConstants::GUEST_DATA,
                    [
                        'email' => $order->getCustomerEmail(),
                        'lastname' => $order->getBillingAddress()->getLastname()
                    ]
                );

                return $this->resultFactory->create(ResultFactory::TYPE_PAGE);
            } catch (\Exception $e) {
                null;
            }
        }

        return $this->resultRedirectFactory->create()->setPath(
            $this->configProvider->getUrlPrefix() .'/guest/login'
        );
    }
}
