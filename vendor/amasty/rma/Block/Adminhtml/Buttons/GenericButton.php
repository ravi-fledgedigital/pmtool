<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Block\Adminhtml\Buttons;

use Amasty\Rma\Controller\Adminhtml\RegistryConstants;
use Amasty\Rma\Model\Request\Repository;
use Magento\Backend\Block\Widget\Context;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class GenericButton
{
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $authorization;

    /**
     * @var Repository
     */
    protected $requestRepository;

    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        Repository $requestRepository
    ) {
        $this->urlBuilder = $context->getUrlBuilder();
        $this->request = $context->getRequest();
        $this->orderRepository = $orderRepository;
        $this->requestRepository = $requestRepository;
        $this->authorization = $context->getAuthorization() ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\AuthorizationInterface::class);
    }

    /**
     * Generate url by route and parameters
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->urlBuilder->getUrl($route, $params);
    }

    /**
     * @return null|int
     */
    public function getRequestId()
    {
        return (int)$this->request->getParam(RegistryConstants::REQUEST_ID);
    }

    /**
     * @param $orderId
     *
     * @return OrderInterface
     */
    public function getOrderById($orderId)
    {
        return $this->orderRepository->get($orderId);
    }
}
