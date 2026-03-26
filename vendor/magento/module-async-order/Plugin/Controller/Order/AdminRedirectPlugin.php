<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AsyncOrder\Plugin\Controller\Order;

use Closure;
use Exception;
use Magento\AsyncOrder\Model\OrderManagement;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\Adminhtml\Order\View;

/**
 * Redirect to Orders grid if Order is unprocessed
 */
class AdminRedirectPlugin
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var RedirectFactory
     */
    private $resultRedirectFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @param RedirectFactory $resultRedirectFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param RequestInterface $request
     * @param DeploymentConfig $deploymentConfig
     */
    public function __construct(
        RedirectFactory          $resultRedirectFactory,
        OrderRepositoryInterface $orderRepository,
        RequestInterface         $request,
        DeploymentConfig         $deploymentConfig
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->orderRepository = $orderRepository;
        $this->request = $request;
        $this->deploymentConfig = $deploymentConfig;
    }

    /**
     * Order around execute plugin.
     *
     * @param View $subject
     * @param Closure $proceed
     * @return ResultInterface
     * @throws FileSystemException
     * @throws RuntimeException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        View    $subject,
        Closure $proceed
    ): ResultInterface {
        if (!$this->deploymentConfig->get(OrderManagement::ASYNC_ORDER_OPTION_PATH)) {
            return $proceed();
        }

        $orderId = $this->request->getParam('order_id');
        try {
            $order = $this->orderRepository->get($orderId);
        } catch (Exception $exception) {
            return $proceed();
        }

        if ($order->getStatus() == OrderManagement::STATUS_RECEIVED) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('sales/*/');
            return $resultRedirect;
        }

        return $proceed();
    }
}
