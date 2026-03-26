<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductRecommendationsAdmin\Controller\Adminhtml\Index;

use Magento\Backend\App\AbstractAction;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\ProductRecommendationsAdmin\Model\ServiceClientInterface;

/**
 * Controller responsible for dealing with the requests from the react app.
 */
class Middleware extends AbstractAction
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var ServiceClientInterface
     */
    private $serviceClient;

    /**
     * @param Context $context
     * @param ServiceClientInterface $serviceClient
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        ServiceClientInterface $serviceClient,
        JsonFactory $resultJsonFactory
    ) {
        $this->serviceClient = $serviceClient;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * Execute middleware call
     */
    public function execute()
    {
        $jsonResult = $this->resultJsonFactory->create();

        $method = $this->getRequest()->getParam('method', 'GET');
        $uri = $this->getRequest()->getParam('uri', '');
        $apiVersion = $this->getRequest()->getParam('apiVersion', '');
        $payload = $this->getRequest()->getParam('payload', '');

        $url = $this->serviceClient->getUrl($apiVersion, $uri);
        $result = $this->serviceClient->request($method, $url, $payload);

        return $jsonResult->setData($result);
    }

    /**
     * Check is user can access to Product Recommendations
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_ProductRecommendationsAdmin::product_recommendations');
    }
}
