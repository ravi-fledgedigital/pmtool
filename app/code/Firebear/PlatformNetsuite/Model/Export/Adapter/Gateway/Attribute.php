<?php

namespace Firebear\PlatformNetsuite\Model\Export\Adapter\Gateway;

use Firebear\ImportExport\Logger\Logger;
use Firebear\ImportExport\Traits\General as GeneralTrait;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use NetSuite\Classes\AddRequest;
use NetSuite\Classes\AddResponse;
use NetSuite\Classes\CustomList;
use NetSuite\Classes\CustomListCustomValue;
use NetSuite\Classes\CustomListCustomValueList;
use NetSuite\Classes\ItemCustomField;
use NetSuite\Classes\ItemCustomFieldItemSubType;
use NetSuite\Classes\RecordType;
use NetSuite\Classes\UpdateRequest;
use NetSuite\Classes\UpdateResponse;
use NetSuite\NetSuiteService;
use Symfony\Component\Console\Output\ConsoleOutput;

class Attribute
{
    use GeneralTrait;

    /**
     * @var Netsuite Config Data
     */
    private $config;

    /**
     * @var Scope Config data
     */
    private $scopeConfig;

    /**
     * @var \Magento\Customer\Model\ResourceModel\CustomerRepository
     */
    private $customerRepository;

    /**
     * @var \Magento\Customer\Model\ResourceModel\AddressRepository
     */
    private $addressRepository;

    /**
     * @var array
     */
    private $behaviorData = [];

    /**
     * @var Config
     */
    protected $eavConfig;

    /**
     * Custom item field prefix
     */
    const CUSTOM_ITEM_FIELD_PREFIX = 'custitem';

    /**
     * @var array
     */
    protected $mappingFieldType = [
        'multiselect' => '_multipleSelect',
        'text' => '_freeFormText',
        'select' => '_listRecord',
        'textarea' => '_textArea',
        'texteditor' => '_inlineHtml',
        'date' => '_date',
        'media_image' => '_image'
    ];

    /**
     * @var array
     */
    protected $customItemFieldsSubtype = [
        0 => ItemCustomFieldItemSubType::_both,
        1 => ItemCustomFieldItemSubType::_purchase,
        2 => ItemCustomFieldItemSubType::_sale,

    ];

    /**
     * Customer constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param ConsoleOutput $output
     * @param Config $eavConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        ConsoleOutput $output,
        Config $eavConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->_logger = $logger;
        $this->output = $output;
        $this->eavConfig = $eavConfig;
    }

    /**
     * Set behavior data
     *
     * @param $data
     *
     * @return $this
     */
    public function setBehaviorData($data)
    {
        $this->behaviorData = $data;

        return $this;
    }

    /**
     * Get behavior data
     *
     * @return array
     */
    public function getBehaviorData()
    {
        return $this->behaviorData;
    }

    /**
     * @param array $data
     *
     * @return mixed|UpdateResponse
     */
    public function exportAttribute($data)
    {
        $behaviorData = $this->getBehaviorData();
        $options = [
            'connection_timeout' => 6000,
            'keep_alive' => true
        ];
        if ($data['frontend_label']) {
            $service = new NetSuiteService($this->getConfig(), $options);
            $attribute = $this->getCustomItemField($data, $behaviorData);

            $response = $this->updateAttribute($service, $attribute);
            $status = $response->writeResponse->status->isSuccess;
            if (!$status) {
                $attribute = $this->addList(
                    $service,
                    $data,
                    $attribute,
                    $behaviorData['custom_item_field_enable_matrix_option']
                );
                $attribute->scriptId = $data['attribute_code'];
                $response = $this->addAttribute($service, $attribute);
            } else {
                $itemCustomFieldId = $response->writeResponse->baseRef->internalId;
                $this->updateList($service, $data, $itemCustomFieldId);
            }
            if ($response->writeResponse->status->isSuccess) {
                $successMessage = __(
                    'The %1 attribute was successfully imported to Netsuite. NetSuite internal id: %2',
                    $data['attribute_code'],
                    $response->writeResponse->baseRef->internalId
                );
                $this->addLogWriteln($successMessage, $this->output);
            } else {
                $errorMessage = __(
                    'Attribute not exported to NetSuite. Message: %1',
                    [
                        $response->writeResponse->status->statusDetail[0]->message
                    ]
                );
                $this->addLogWriteln($errorMessage, $this->output, 'error');
            }
            return $response;
        }
        return false;
    }

    /**
     * @param ItemCustomField $attribute
     * @param NetSuiteService $service
     * @return UpdateResponse
     */
    public function updateAttribute($service, $attribute)
    {
        $request = new \NetSuite\Classes\UpdateRequest();
        $attribute->scriptId = self::CUSTOM_ITEM_FIELD_PREFIX . $attribute->label;
        $request->record = $attribute;
        return $service->update($request);
    }

    /**
     * @param NetSuiteService $service
     * @param ItemCustomField $attribute
     * @return AddResponse
     */
    public function addAttribute($service, $attribute)
    {
        $request = new \NetSuite\Classes\AddRequest();
        $request->record = $attribute;
        return $service->add($request);
    }

    /**
     * @param NetSuiteService $service
     * @param array $attributeData
     * @param int $itemCustomFieldId
     * @return UpdateResponse
     */
    public function updateList($service, $attributeData, $itemCustomFieldId)
    {
        $getRequest = new \NetSuite\Classes\GetRequest();
        $getRequest->baseRef = new \NetSuite\Classes\RecordRef();
        $getRequest->baseRef->internalId = $itemCustomFieldId;
        $getRequest->baseRef->type = RecordType::itemCustomField;
        $getResponse = $service->get($getRequest);
        $customListId = $getResponse->readResponse->record->selectRecordType->internalId;
        $customList = new CustomList();
        $customList->internalId = $customListId;
        $customList->name = $attributeData['frontend_label'];
        $customListCustomValueList = new CustomListCustomValueList();
        $customListCustomValueList->replaceAll = true;
        $eavAttributeOptions = $this->eavConfig->getAttribute('catalog_product', $attributeData['attribute_code']);
        $attributeOptions = $eavAttributeOptions->getSource()->getAllOptions();
        foreach ($attributeOptions as $options) {
            if ($options['value']) {
                $customListCustomValue = new CustomListCustomValue();
                $customListCustomValue->value = $options['label'];
                $customListCustomValueList->customValue[] = $customListCustomValue;
            }
        }
        $customList->customValueList = $customListCustomValueList;
        $getRequestUpdate = new UpdateRequest();
        $getRequestUpdate->record = $customList;
        return $service->update($getRequestUpdate);
    }

    /**
     * @param NetSuiteService $service
     * @param array $attributeData
     * @param ItemCustomField $attribute
     * @return ItemCustomField
     * @throws LocalizedException
     */
    public function addList($service, $attributeData, $attribute, $isMatrixOption = false)
    {
        $customList = new CustomList();
        $customList->name = $attributeData['frontend_label'];
        $customListCustomValueList = new CustomListCustomValueList();
        $eavAttributeOptions = $this->eavConfig->getAttribute('catalog_product', $attributeData['attribute_code']);
        $attributeOptions = $eavAttributeOptions->getSource()->getAllOptions();
        foreach ($attributeOptions as $options) {
            if ($options['value']) {
                $customListCustomValue = new CustomListCustomValue();
                $customListCustomValue->value = $options['label'];
                $customListCustomValue->abbreviation = $options['label'];
                $customListCustomValueList->customValue[] = $customListCustomValue;
            }
        }
        $customList->customValueList = $customListCustomValueList;
        if (count($attributeOptions) && $isMatrixOption) {
            $customList->isMatrixOption = $isMatrixOption;
        }
        $getAddRequest = new AddRequest();
        $getAddRequest->record = $customList;
        $response = $service->add($getAddRequest);
        if ($response->writeResponse->status->isSuccess) {
            $attribute->selectRecordType = $response->writeResponse->baseRef;
        }
        return $attribute;
    }

    /**
     * @param array $data
     * @param array $behaviorData
     * @return ItemCustomField
     */
    protected function getCustomItemField($data, $behaviorData)
    {
        $attribute = new ItemCustomField();
        $attribute->label = $data['frontend_label'];
        if ($behaviorData['set_attribute_default_value']) {
            $attribute->defaultValue = $data['default_value'];
        }
        $attribute->fieldType = (isset($this->mappingFieldType[$data['frontend_input']])) ?
            $this->mappingFieldType[$data['frontend_input']] : '_freeFormText';
        $attribute->appliesToInventory = $behaviorData['custom_item_field_applies_to_inventory'];
        $attribute->appliesToNonInventory = $behaviorData['custom_item_field_applies_to_non_inventory'];
        $attribute->appliesToGroup = $behaviorData['custom_item_field_applies_to_group'];
        $attribute->appliesToKit = $behaviorData['custom_item_field_applies_to_kit'];
        $attribute->appliesToService = $behaviorData['custom_item_field_applies_to_service'];
        $attribute->appliesToOtherCharge = $behaviorData['custom_item_field_applies_to_othercharge'];
        $attribute->itemSubType = $this->customItemFieldsSubtype[$behaviorData['custom_item_field_subtype']];
        $attribute->showInList = $data['used_in_product_listing'];
        $attribute->itemMatrix = $behaviorData['custom_item_field_enable_matrix_option'];
        return $attribute;
    }

    /**
     * @return array
     */
    protected function getConfig()
    {
        if (empty($this->config)) {

            $behaviorData = $this->getBehaviorData();

            if (!empty($behaviorData)) {
                $this->config = [
                    "endpoint" => \trim($behaviorData['netsuite_credentials_endpoint']),
                    "host"     => \trim($behaviorData['netsuite_credentials_host']),
                    "account"  => \trim($behaviorData['netsuite_credentials_account']),
                    "consumerKey" => \trim($behaviorData['netsuite_credentials_consumer_key']),
                    "consumerSecret" => \trim($behaviorData['netsuite_credentials_consumer_secret']),
                    "token" => \trim($behaviorData['netsuite_credentials_token']),
                    "tokenSecret" => \trim($behaviorData['netsuite_credentials_token_secret']),
                    "use_old_http_protocol_version" => \trim(
                        $behaviorData['netsuite_credentials_use_old_http_protocol_version']
                    )
                ];
            } else {
                $this->config = [
                    "endpoint" => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/endpoint')),
                    "host"     => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/host')),
                    "account"  => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/account')),
                    "consumerKey" => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/consumerKey')),
                    "consumerSecret" => \trim(
                        $this->scopeConfig->getValue('firebear_importexport/netsuite/consumerSecret')
                    ),
                    "token" => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/token')),
                    "tokenSecret" => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/tokenSecret')),
                    "use_old_http_protocol_version" => \trim(
                        $this->scopeConfig->getValue(
                            'firebear_importexport/netsuite/use_old_http_protocol_version'
                        )
                    )
                ];
            }
        }
        return $this->config;
    }
}
