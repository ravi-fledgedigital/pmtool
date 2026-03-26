<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Guest;

use Amasty\Rma\Api\CustomerRequestRepositoryInterface;
use Amasty\Rma\Api\GuestCreateRequestProcessInterface;
use Amasty\Rma\Controller\FrontendRma;
use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Model\Cookie\HashChecker;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Registry;
use Magento\Sales\Api\OrderRepositoryInterface;

class Save extends \Magento\Framework\App\Action\Action
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var CustomerRequestRepositoryInterface
     */
    private $requestRepository;

    /**
     * @var GuestCreateRequestProcessInterface
     */
    private $guestCreateRequestProcess;

    /**
     * @var HashChecker
     */
    private $hashChecker;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var FrontendRma
     */
    private $frontendRma;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Registry $registry,
        GuestCreateRequestProcessInterface $guestCreateRequestProcess,
        ConfigProvider $configProvider,
        CustomerRequestRepositoryInterface $requestRepository,
        FrontendRma $frontendRma,
        HashChecker $hashChecker,
        Context $context
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->registry = $registry;
        $this->requestRepository = $requestRepository;
        $this->guestCreateRequestProcess = $guestCreateRequestProcess;
        $this->hashChecker = $hashChecker;
        $this->configProvider = $configProvider;
        $this->frontendRma = $frontendRma;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     */
    public function execute()
    {
        if (!($secretKey = $this->getRequest()->getParam('secret'))) {
            return $this->resultRedirectFactory->create()->setPath(
                $this->configProvider->getUrlPrefix() . '/guest/login'
            );
        }

        $orderId = $this->guestCreateRequestProcess->getOrderIdBySecretKey($secretKey);
        try {
            $order = $this->orderRepository->get($orderId);
        } catch (\Exception $exception) {
            $orderId = false;
        }

        if (!$orderId) {
            $this->messageManager->addWarningMessage('Order Not Found');

            return $this->resultRedirectFactory->create()->setPath(
                $this->configProvider->getUrlPrefix() . '/guest/login'
            );
        }

        $items = $this->getRequest()->getParam('items');
        if (!is_array($items) || !$items) {
            $this->messageManager->addWarningMessage(__('Items were not selected'));

            return $this->resultRedirectFactory->create()->setPath(
                $this->configProvider->getUrlPrefix() . '/guest/newreturn',
                ['secret' => $secretKey]
            );
        }

        if ($this->configProvider->isReturnPolicyEnabled() && !$this->getRequest()->getParam('rmapolicy')) {
            $this->messageManager->addWarningMessage(__('You didn\'t agree to Privacy policy'));

            return $this->resultRedirectFactory->create()->setPath(
                $this->configProvider->getUrlPrefix() . '/guest/newreturn',
                ['secret' => $secretKey]
            );
        }

        $request = $this->requestRepository->create(
            $this->frontendRma->processNewRequest(
                $this->requestRepository,
                $order,
                $this->getRequest()
            ),
            $secretKey
        );

        $files = [];
        if ($jsonFiles = $this->getRequest()->getParam('attach-files')) {
            $files = json_decode($jsonFiles, true);
        }
        if (!empty($comment = $this->getRequest()->getParam('comment')) || !(empty($files))) {
            $this->frontendRma->saveNewReturnMessage($request, $comment, $files);
        }

        $this->hashChecker->setHash($request->getUrlHash());
        $this->guestCreateRequestProcess->deleteBySecretKey($secretKey);

        return $this->resultRedirectFactory->create()->setPath(
            $this->configProvider->getUrlPrefix() . '/guest/view',
            ['request' => $request->getUrlHash()]
        );
    }
}
