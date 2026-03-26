<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualProductRecommendations\Controller\Adminhtml\Index;

use Magento\Backend\App\AbstractAction;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\ServicesId\Model\ServicesClientInterface;
use Magento\ServicesId\Model\ServicesConfigInterface;

/**
 * Controller responsible for communicating with the Magento SaaS Registry service
 */
class EnableVisualRecommendations extends AbstractAction
{
    private const VISUAL_RECOMMENDATIONS_FEATURE = 'VISUAL_RECOMMENDATIONS';

    /**
     * @var ServicesConfigInterface
     */
    private $servicesConfig;

    /**
     * @var ServicesClientInterface
     */
    private $servicesClient;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @param Context $context
     * @param ServicesConfigInterface $servicesConfig
     * @param ServicesClientInterface $servicesClient
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        ServicesConfigInterface $servicesConfig,
        ServicesClientInterface $servicesClient,
        JsonFactory $resultJsonFactory
    ) {
        $this->servicesConfig = $servicesConfig;
        $this->servicesClient = $servicesClient;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * Execute middleware call
     *
     * @return ResultInterface
     */
    public function execute() : ResultInterface
    {
        $jsonResult = $this->resultJsonFactory->create();
        $method = $this->getRequest()->getParam('method', 'GET');

        if ($method === 'GET') {
            $path = 'registry/environments/' . $this->servicesConfig->getEnvironmentId();
            $url = $this->servicesConfig->getRegistryApiUrl($path);
            $response = $this->servicesClient->request($method, $url);
            $isEnabled = false;
            if (isset($response['featureSet'])) {
                $isEnabled = in_array( 'Visual Recommendations', $response['featureSet']);
            }
            $result = ['visualRecommendationsEnabled' => $isEnabled];
        } else {
            $path = sprintf(
                'registry/environments/%s/feature/%s',
                $this->servicesConfig->getEnvironmentId(),
                self::VISUAL_RECOMMENDATIONS_FEATURE
            );
            $url = $this->servicesConfig->getRegistryApiUrl($path);
            $result = $this->servicesClient->request($method, $url);
        }

        return $jsonResult->setData($result);
    }
}
