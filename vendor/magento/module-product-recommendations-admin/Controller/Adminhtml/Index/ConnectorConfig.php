<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductRecommendationsAdmin\Controller\Adminhtml\Index;

use Magento\Backend\App\AbstractAction;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Cache\Type\Config as CacheConfig;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\ServicesId\Model\ServicesConfig;
use Psr\Log\LoggerInterface;

/**
 * Controller responsible for setting configuration values from the Product Recommendations Admin UI
 */
class ConnectorConfig extends AbstractAction
{
    const CONFIG_PATH_ALTERNATE_ENVIRONMENT_ENABLED = 'services_connector/product_recommendations/alternate_environment_enabled';
    const CONFIG_PATH_ALTERNATE_ENVIRONMENT_ID = 'services_connector/product_recommendations/alternate_environment_id';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Json $serializer
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Json $serializer,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        LoggerInterface $logger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->serializer = $serializer;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Execute connector controller call
     */
    public function execute()
    {
        $jsonResult = $this->resultJsonFactory->create();
        $environmentId = $this->getRequest()->getParam('environmentId');
        $apiKey = $this->getRequest()->getParam('apiKey');
        $alternateEnvironmentId = $this->getRequest()->getParam('alternateEnvironmentId');
        $isAlternateEnvironmentEnabled = $this->getRequest()->getParam('isAlternateEnvironmentEnabled');

        if ($environmentId && $apiKey) {
            $configs = [
                ServicesConfig::CONFIG_PATH_ENVIRONMENT_ID => $environmentId,
                ServicesConfig::CONFIG_PATH_SERVICES_CONNECTOR_PRODUCTION_API_KEY => $apiKey
            ];
        }

        if ($isAlternateEnvironmentEnabled || $alternateEnvironmentId) {
            $configs = [
                self::CONFIG_PATH_ALTERNATE_ENVIRONMENT_ENABLED =>
                    (int) $this->serializer->unserialize($isAlternateEnvironmentEnabled),
                self::CONFIG_PATH_ALTERNATE_ENVIRONMENT_ID => $alternateEnvironmentId
            ];
        }

        if (!empty($configs)) {
            $result = $this->setConfigValues($configs);
        } else {
            $result = ['message' => 'Request is missing required values'];
        }
        return $jsonResult->setData($result);
    }

    /**
     * Set values to store configuration
     *
     * @param array $configs
     * @return array
     */
    public function setConfigValues(array $configs) : array
    {
        try {
            foreach ($configs as $key => $value) {
                $this->configWriter->save($key, $value);
            }
            $this->cacheTypeList->cleanType(CacheConfig::TYPE_IDENTIFIER);
            $result = ['message' => 'Success'];
        } catch (\Exception $ex) {
            $result = ['message' => 'An error occurred'];
            $this->logger->error($ex->getMessage());
        }
        return $result;
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
