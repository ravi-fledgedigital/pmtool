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
use Magento\ServicesId\Model\MerchantRegistryProvider;
use Magento\ServicesId\Model\ServicesConfigInterface;

/**
 * Controller responsible for getting all merchant registry environments data for settings
 */
class Environments extends AbstractAction
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var MerchantRegistryProvider
     */
    private $merchantRegistryProvider;

    /**
     * @var ServicesConfigInterface
     */
    private $servicesConfig;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param MerchantRegistryProvider $merchantRegistryProvider
     * @param ServicesConfigInterface $servicesConfig
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        MerchantRegistryProvider $merchantRegistryProvider,
        ServicesConfigInterface $servicesConfig
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->merchantRegistryProvider = $merchantRegistryProvider;
        $this->servicesConfig = $servicesConfig;
        parent::__construct($context);
    }

    /**
     * Execute category controller call
     *
     */
    public function execute()
    {
        $jsonResult = $this->resultJsonFactory->create();
        $result = $this->getEnvironmentOptionsArray();
        return $jsonResult->setData($result);
    }

    /**
     * Options getter
     *
     * @return array
     */
    private function getEnvironmentOptionsArray() : array
    {
        $data = $this->getProjectEnvironmentOptionsArray();

        $optionsArray = [];
        if (!empty($data)) {
            $projectId = $this->servicesConfig->getProjectId();
            foreach ($data[$projectId] as $key => $value) {
                $optionsArray[] = [
                    'value' => $value['value'],
                    'label' => $value['text']
                ];
            }
        } else {
            $optionsArray = [
                ['value' => null, 'label' => 'No Environments Found']
            ];
        }
        return $optionsArray;
    }

    /**
     * Options getter for environments by project
     *
     * @return array
     */
    private function getProjectEnvironmentOptionsArray() : array
    {
        $data = $this->merchantRegistryProvider->getMerchantRegistry();

        $projectEnvironments = [];
        if (!empty($data) && !isset($data['error'])) {
            $projects = [];
            foreach ($data as $key => $value) {
                $projects[] = $value['projectId'];
            }
            $projects = array_unique($projects);

            foreach ($data as $key => $value) {
                foreach ($projects as $project) {
                    if ($value['projectId'] == $project) {
                        $projectEnvironments[$project][$value['environmentId']] = [
                            'value' => $value['environmentId'],
                            'text' => $value['environmentName'] . ' [' . __('Type: ') . $value['environmentType'] . ']'
                        ];
                    }
                }
            }
        }
        return $projectEnvironments;
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
