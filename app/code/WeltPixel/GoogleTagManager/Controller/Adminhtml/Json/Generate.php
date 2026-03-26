<?php
namespace WeltPixel\GoogleTagManager\Controller\Adminhtml\Json;

use Magento\Backend\App\Action;

/**
 * Class Generate
 * @package WeltPixel\GoogleTagManager\Controller\Adminhtml\Json
 */
class Generate extends Action {

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \WeltPixel\GoogleTagManager\Model\JsonGenerator
     */
    protected $jsonGenerator;

    /**
     * Version constructor.
     *
     * @param \WeltPixel\GoogleTagManager\Model\JsonGenerator $jsonGenerator
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        \WeltPixel\GoogleTagManager\Model\JsonGenerator $jsonGenerator,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->jsonGenerator = $jsonGenerator;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $jsonUrl = null;
        $msg = $this->_validateParams($params);

        $formData = [];
        parse_str($params['form_data'], $formData);
        $apiParams = $this->_parseParams($formData);

        if (!count($msg)) {
            try {
                $jsonUrl = $this->jsonGenerator->
                generateItemJson(
                    trim($params['account_id']),
                    trim($params['container_id']),
                    trim($params['ua_tracking_id']),
                    trim($params['ip_anonymization']),
                    trim($params['display_advertising']),
                    trim($params['conversion_enabled']),
                    trim($params['conversion_id']),
                    trim($params['conversion_label']),
                    trim($params['conversion_currency_code']),
                    trim($params['remarketing_enabled']),
                    trim($params['remarketing_conversion_code']),
                    trim($params['remarketing_conversion_label']),
                    trim($params['public_id']),
                    $apiParams
                );
                $msg[] = __('Json was generated successfully. You can download the file by clicking on the Download Json button.');
            } catch (\Exception $ex) {
                $msg[] = $ex->getMessage();
            }
        }

        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData([
            'msg' => $msg,
            'jsonUrl' => $jsonUrl
        ]);
        return $resultJson;
    }

    /**
     * @param $params
     * @return array
     */
    protected function _validateParams($params)
    {
        $accountId = $params['account_id'] ?? '';
        $containerId = $params['container_id'] ?? '';
        $uaTrackingId = $params['ua_tracking_id'] ?? '';
        $publicId = $params['public_id'] ?? '';
        $conversionEnabled  = $params['conversion_enabled'] ?? '';
        $conversionId =  $params['conversion_id'] ?? '';
        $conversionLabel =  $params['conversion_label'] ?? '';
        $conversionCurrencyCode =  $params['conversion_currency_code'] ?? '';
        $remarketingEnabled  = $params['remarketing_enabled'] ?? '';
        $remarketingEnabledConversionCode = $params['remarketing_conversion_code'] ?? '';

        $msg = [];

        if (!strlen(trim($accountId))) {
            $msg[] = __('Account ID must be specified');
        }

        if (!strlen(trim($containerId))) {
            $msg[] = __('Container ID must be specified');
        }

        if (!strlen(trim($uaTrackingId))) {
            $msg[] = __('Universal Tracking ID must be specified');
        }

        if (!strlen(trim($publicId))) {
            $msg[] = __('Public ID must be specified');
        }

        if ($conversionEnabled) {
            if (!strlen(trim($conversionId))) {
                $msg[] = __('Conversion ID must be specified');
            }

            if (!strlen(trim($conversionLabel))) {
                $msg[] = __('Conversion Label must be specified');
            }

            if (!strlen(trim($conversionCurrencyCode))) {
                $msg[] = __('Conversion Currency Code must be specified');
            }
        }

        if ($remarketingEnabled) {
            if (!strlen(trim($remarketingEnabledConversionCode))) {
                $msg[] = __('Remarketing Conversion Code must be specified');
            }
        }

        return $msg;
    }

    /**
     * @param $formData
     * @return array
     */
    protected function _parseParams($formData) {
        $productDimensions = [
            'track_stockstatus' => [
                'enabled' => false
            ],
            'track_reviewscount' => [
                'enabled' => false
            ],
            'track_reviewsscore' => [
                'enabled' => false
            ],
            'track_saleproduct' => [
                'enabled' => false
            ]
        ];

        $customDimensions = [
            'custom_dimension_customerid' => [
                'enabled' => false
            ],
            'custom_dimension_customergroup' => [
                'enabled' => false
            ],
            'custom_dimension_pagetype' => [
                'enabled' => false
            ]
        ];

        $productCustomAttributeDimensions = [];


        if (isset($formData['groups']['general']['fields'])) {
            $formFields = $formData['groups']['general']['fields'];

            /** Gather the product dimensions */
            foreach ($productDimensions as $trackId => &$options) {
                $options['enabled'] = $formFields[$trackId]['value'];

                $indexnumber = $trackId.'_indexnumber';
                if ( isset ($formFields[$indexnumber]) ) {
                    $options['type'] = \WeltPixel\GoogleTagManager\Model\Dimension::DIMENSION_TYPE . $formFields[$indexnumber]['value'];
                    $options['track_option'] = \WeltPixel\GoogleTagManager\Model\Dimension::DIMENSION_TYPE;
                    $options['index'] = $formFields[$indexnumber]['value'];
                }

            }

            for ($i = 1; $i<=5; $i++) {
                $customAttributeTrackPrefix = 'track_custom_attribute_'. $i;
                $indexnumber = $customAttributeTrackPrefix .'_indexnumber';
                $type = $customAttributeTrackPrefix.'_type';
                $attributeCode = $customAttributeTrackPrefix.'_code';
                $attributeName = $customAttributeTrackPrefix.'_name';

                if (isset($formFields[$customAttributeTrackPrefix]) && $formFields[$customAttributeTrackPrefix]['value'] == 1) {
                    $options = [];
                    $options['enabled'] = true;
                    $options['type'] = $formFields[$type]['value'] . $formFields[$indexnumber]['value'];
                    $options['track_option'] = $formFields[$type]['value'];
                    $options['index'] = $formFields[$indexnumber]['value'];
                    $options['attribute_code'] = $formFields[$attributeCode]['value'];
                    $options['attribute_name'] = $formFields[$attributeName]['value'];
                    $productCustomAttributeDimensions[] = $options;
                }
            }



            /** Gather the customer and hit dimensions */
            foreach ($customDimensions as $index => &$options) {
                $options['enabled'] = $formFields[$index]['value'];
                $indexnumber = $index.'_indexnumber';
                if ( isset ($formFields[$indexnumber]) ) {
                    $options['index'] = $formFields[$indexnumber]['value'];
                }
            }
        }

        return [
            'product_dimensions' => $productDimensions,
            'custom_dimensions' => $customDimensions,
            'product_custom_attributes_dimensions' => $productCustomAttributeDimensions
        ];
    }
}
