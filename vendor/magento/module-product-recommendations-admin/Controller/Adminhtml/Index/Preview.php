<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\ProductRecommendationsAdmin\Controller\Adminhtml\Index;

use Laminas\Http\Request;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\ProductRecommendationsAdmin\Model\ServiceClientInterface;
use Psr\Log\LoggerInterface;

/*
 * Controller action to handle preview requests for preconfigured recommendations.
 */
class Preview extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_ProductRecommendationsAdmin::product_recommendations';

    /**
     * URI for preconfigured recommendations endpoint
     */
    private const PRECONFIGURED_RECOMMENDATIONS_URI = 'recs/v1/precs/preconfigured';

    /**
     * @param Context $context
     * @param ServiceClientInterface $serviceClient
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        private ServiceClientInterface $serviceClient,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        if (!$this->getRequest()->isAjax()) {
            return $this->_redirect('*/*/index');
        }

        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $response->setData(
                $this->serviceClient->request(
                    Request::METHOD_POST,
                    self::PRECONFIGURED_RECOMMENDATIONS_URI,
                    $this->getRequest()->getContent(),
                ),
            );
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $response->setHttpResponseCode(500);
        }

        return $response;
    }

    /**
     * @inheritDoc
     */
    public function _processUrlKeys()
    {
        $isValid = false;

        if ($this->_auth->isLoggedIn()) {
            if ($this->_backendUrl->useSecretKey()) {
                $isValid = $this->_validateSecretKey();
            } else {
                $isValid = true;
            }
        }
        if (!$isValid) {
            $error = json_encode(
                [
                    'errors' => [
                        [
                            'message' => 'Authentication failed'
                        ]
                    ]
                ]
            );
            $this->getResponse()->representJson($error);
        }

        return $isValid;
    }
}
