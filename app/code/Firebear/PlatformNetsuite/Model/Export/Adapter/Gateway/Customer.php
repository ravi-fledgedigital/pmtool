<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\PlatformNetsuite\Model\Export\Adapter\Gateway;

use Firebear\ImportExport\Traits\General as GeneralTrait;
use NetSuite\Classes\RecordType;
use Symfony\Component\Console\Output\ConsoleOutput;
use Firebear\ImportExport\Logger\Logger;

/**
 * Netsuite customer gateway
 */
class Customer extends AbstractGateway
{
    use GeneralTrait;

    /**
     * @var \Magento\Customer\Model\ResourceModel\CustomerRepository
     */
    private $customerRepository;

    /**
     * @var \Magento\Customer\Model\ResourceModel\AddressRepository
     */
    private $addressRepository;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Customer constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository
     * @param \Magento\Customer\Model\ResourceModel\AddressRepository $addressRepository
     * @param Logger $logger
     * @param ConsoleOutput $output
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository,
        \Magento\Customer\Model\ResourceModel\AddressRepository $addressRepository,
        Logger $logger,
        ConsoleOutput $output
    ) {
        parent::__construct($scopeConfig);
        $this->scopeConfig = $scopeConfig;
        $this->customerRepository = $customerRepository;
        $this->addressRepository = $addressRepository;
        $this->_logger = $logger;
        $this->output = $output;
    }

    /**
     * @param $data
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function exportCustomer($data)
    {
        $this->initService();
        if ($data['entity'] == 'customer') {
            $customer = $this->customerRepository->get($data['email']);
            $this->addCustomer($customer, $data);
        } elseif ($data['entity'] == 'customer_address') {
            $address = $this->addressRepository->getById($data['_entity_id']);
            $this->addCustomerAddress($address, $data);
        }
    }

    /**
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @param $data
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function addCustomerAddress(\Magento\Customer\Api\Data\AddressInterface $address, $data = [])
    {
        $addressNetsuiteInternalId = '';
        $customerId = $address->getCustomerId();
        $customer = $this->customerRepository->getById($customerId);
        $customerNetsuiteInternalId = $customer->getCustomAttribute('netsuite_internal_id');

        if (!empty($customerNetsuiteInternalId)) {
            $customerNetsuiteInternalId = $customerNetsuiteInternalId->getValue();
        }

        $netsuiteCustomer = $this->getCustomer($customerNetsuiteInternalId);
        $netsuiteAddress = new \NetSuite\Classes\Address();
        $netsuiteAddress->addr1 = implode(',', $address->getStreet());
        $netsuiteAddress->addrPhone = $address->getTelephone();

        if (isset($this->countryMapping[$address->getCountryId()])) {
            $netsuiteAddress->country = $this->countryMapping[$address->getCountryId()];
        }

        $netsuiteAddress->city = $address->getCity();
        $netsuiteAddress->state = $address->getRegion()->getRegionCode();
        $netsuiteAddress->zip = $address->getPostcode();
        $netsuiteAddressBook = new \NetSuite\Classes\CustomerAddressbook();
        $netsuiteAddressBook->addressbookAddress = $netsuiteAddress;

        if (!empty($netsuiteCustomer)) {
            $addressNetsuiteInternalIdAttribute = $address->getCustomAttribute('netsuite_internal_id');
            if (!empty($addressNetsuiteInternalIdAttribute)) {
                $addressNetsuiteInternalId = $addressNetsuiteInternalIdAttribute->getValue();
            }
            $data['netsuiteInternalId'] = $netsuiteCustomer->internalId;
            $addressBookList = $netsuiteCustomer->addressbookList;

            if (!empty($addressNetsuiteInternalId) && $addressBookList) {
                $existsInAddressBook = false;
                foreach ($addressBookList->addressbook as $key => $addreessBook) {
                    if ($addreessBook->internalId == $addressNetsuiteInternalId) {
                        $netsuiteAddressBook->internalId = $addressNetsuiteInternalId;
                        $addressBookList->addressbook[$key] = $netsuiteAddressBook;
                        $existsInAddressBook = true;
                    }
                }
                if (!$existsInAddressBook) {
                    $addressNetsuiteInternalId = null;
                    $addressBookList->addressbook[] = $netsuiteAddressBook;
                }
            } else {
                if (empty($addressBookList)) {
                    $addressBookList = new \NetSuite\Classes\CustomerAddressbookList();
                }
                $addressBookList->addressbook[] = $netsuiteAddressBook;
            }
            $data['addressBookList'] = $addressBookList;
            $updateResponse = $this->updateCustomerAddress($data);
        } else {
            $addressBookList = new \NetSuite\Classes\CustomerAddressbookList();
            $addressBookList->addressbook = $netsuiteAddressBook;
            $data['firstname'] = $customer->getFirstname();
            $data['lastname'] = $customer->getLastname();
            $data['phone'] = $address->getTelephone();
            $data['email'] = $customer->getEmail();
            $data['addressBookList'] = $addressBookList;
            $data['customerGroupId'] = $customer->getGroupId();
            $updateResponse = $this->createCustomer($data);
            if ($updateResponse->writeResponse->status->isSuccess) {
                $customer->setCustomAttribute(
                    'netsuite_internal_id',
                    $updateResponse->writeResponse->baseRef->internalId
                );
                $this->customerRepository->save($customer);
            }
        }

        if ($updateResponse->writeResponse->status->isSuccess && empty($addressNetsuiteInternalId)) {
            $netsuiteCustomer = $this->getCustomer($updateResponse->writeResponse->baseRef->internalId);
            $addressBookListArray = $netsuiteCustomer->addressbookList->addressbook;
            $lastAddedAddress = end($addressBookListArray);
            $addressNetsuiteInternalId = $lastAddedAddress->internalId;
            $address->setCustomAttribute('netsuite_internal_id', $addressNetsuiteInternalId);
            $this->addressRepository->save($address);
        }
        if ($updateResponse->writeResponse->status->isSuccess) {
            $successMessage = __(
                'The Customer %1 was successfully imported to Netsuite',
                $customer->getEmail()
            );
            $this->addLogWriteln($successMessage, $this->output);
        } else {
            $errorMessage = __(
                'The Customer not exported to the Netsuite.'.
                ' Email: %1. Message: %2',
                [
                    $customer->getEmail(),
                    $updateResponse->writeResponse->status->statusDetail[0]->message
                ]
            );
            $this->addLogWriteln($errorMessage, $this->output, 'error');
        }
    }

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param array $data
     */
    public function addCustomer(\Magento\Customer\Api\Data\CustomerInterface $customer, $data = [])
    {
        $customerNetsuiteInternalId = $customer->getCustomAttribute('netsuite_internal_id');

        if (!empty($customerNetsuiteInternalId)) {
            $customerNetsuiteInternalId = $customerNetsuiteInternalId->getValue();
        }
        $netsuiteCustomer = $this->getCustomer($customerNetsuiteInternalId);
        $data['firstname'] = $customer->getFirstname();
        $data['lastname'] = $customer->getLastname();
        $data['email'] = $customer->getEmail();
        $data['customerGroupId'] = $customer->getGroupId();
        if (!empty($netsuiteCustomer)) {
            $data['netsuiteInternalId'] = $netsuiteCustomer->internalId;
            $response = $this->updateCustomer($data);
        } else {
            $response = $this->createCustomer($data);
        }
        if ($response->writeResponse->status->isSuccess) {
            $customer->setCustomAttribute(
                'netsuite_internal_id',
                $response->writeResponse->baseRef->internalId
            );
            $successMessage = __(
                'The customer with email %1 was successfully imported to Netsuite. NetSuite internal id: %2',
                $data['email'],
                $response->writeResponse->baseRef->internalId
            );
            $this->addLogWriteln($successMessage, $this->output);
            if (empty($netsuiteCustomer)) {
                $this->customerRepository->save($customer);
            }
        } else {
            $errorMessage = __(
                ' Customer not exported to NetSuite. Message: %1',
                [
                    $response->writeResponse->status->statusDetail[0]->message
                ]
            );
            $this->addLogWriteln($errorMessage, $this->output, 'error');
        }
    }

    /**
     * @param $internalId
     * @return \NetSuite\Classes\RecordRef|null
     */
    private function getCustomer($internalId)
    {
        if (!empty($internalId)) {
            $getRequest = new \NetSuite\Classes\GetRequest();
            $getRequest->baseRef = new \NetSuite\Classes\RecordRef();
            $getRequest->baseRef->internalId = $internalId;
            $getRequest->baseRef->type = "customer";
            $getResponse = $this->service->get($getRequest);

            if ($getResponse->readResponse->status->isSuccess) {
                $customer = $getResponse->readResponse->record;
                return $customer;
            }
        }
        return null;
    }

    /**
     * @param $data
     * @return \NetSuite\Classes\AddResponse
     */
    private function createCustomer($data)
    {
        $behaviorData = $this->getBehaviorData();
        $customer = new \NetSuite\Classes\Customer();
        $customer->lastName = $data['firstname'];
        $customer->firstName = $data['lastname'];
        $customer->email = $data['email'];
        $customer->subsidiary = $this->getCustomerSubsidiary();
        if ($behaviorData['set_entity_id_for_customer']) {
            $customer->entityId = $data['firstname'] . ' ' . $data['lastname'];
        }

        if (!empty($behaviorData['сustomer_export_company'])) {
            $customer->companyName = (!empty($data['company'])) ?
                $data['company'] : $data['firstname'] . ' ' . $data['lastname'];
        }
        $customer = $this->addCustomField($data, $customer);
        if (!empty($behaviorData['sales_rep_internal_id'])) {
            $salesRep = new \NetSuite\Classes\RecordRef();
            $salesRep->internalId = $behaviorData['sales_rep_internal_id'];
            $customer->salesRep = $salesRep;
        }

        if (!empty($behaviorData['customer_category_internal_id'])) {
            $category = new \NetSuite\Classes\RecordRef();
            $category->internalId = $behaviorData['customer_category_internal_id'];
            $customer->category = $category;
        }

        if (!empty($behaviorData['customer_terms_internal_id'])) {
            $terms = new \NetSuite\Classes\RecordRef();
            $terms->internalId = $behaviorData['customer_terms_internal_id'];
            $customer->terms = $terms;
        }

        if (!empty($behaviorData['customer_lead_source_internal_id'])) {
            $leadSource = new \NetSuite\Classes\RecordRef();
            $leadSource->internalId = $behaviorData['customer_lead_source_internal_id'];
            $customer->leadSource = $leadSource;
        }

        $netsuitePriceLevelMapping = $this->getNetsuitePriceLevelMapping();

        if (!empty($netsuitePriceLevelMapping)
            && isset($netsuitePriceLevelMapping[$data['customerGroupId']])) {
            $priceLevel = new \NetSuite\Classes\RecordRef();
            $priceLevel->internalId = $netsuitePriceLevelMapping[$data['customerGroupId']];
            $customer->priceLevel = $priceLevel;
        }

        if (!empty($data['phone'])) {
            $customer->phone = $data['phone'];
        }

        if (!empty($data['addressBookList'])) {
            $customer->addressbookList = $data['addressBookList'];
        }

        $request = new \NetSuite\Classes\AddRequest();
        $request->record = $customer;
        $response = $this->service->add($request);
        return $response;
    }

    /**
     * @param $data
     * @return \NetSuite\Classes\UpdateResponse
     */
    public function updateCustomer($data)
    {
        $customer = new \NetSuite\Classes\Customer();
        $customer->internalId = $data['netsuiteInternalId'];
        $customer->lastName = $data['firstname'];
        $customer->firstName = $data['lastname'];
        $customer->email = $data['email'];
        $customer->subsidiary = $this->getCustomerSubsidiary();
        $customer = $this->addCustomField($data, $customer);
        $request = new \NetSuite\Classes\UpdateRequest();
        $request->record = $customer;
        $response = $this->service->update($request);
        return $response;
    }

    /**
     * @param $data
     * @return \NetSuite\Classes\UpdateResponse
     */
    public function updateCustomerAddress($data)
    {
        $customer = new \NetSuite\Classes\Customer();
        $customer->internalId = $data['netsuiteInternalId'];
        $customer->addressbookList = $data['addressBookList'];
        $request = new \NetSuite\Classes\UpdateRequest();
        $request->record = $customer;
        $response = $this->service->update($request);
        return $response;
    }

    /**
     * Get Customer subsidiary
     *
     * @return \NetSuite\Classes\RecordRef
     */
    private function getCustomerSubsidiary()
    {
        $subsidiary = new \NetSuite\Classes\RecordRef();
        $subsidiaryInternalId = null;
        $behaviorData = $this->getBehaviorData();
        if (!empty($behaviorData['subsidiary_internal_id'])) {
            $subsidiaryInternalId = $behaviorData['subsidiary_internal_id'];
        } else {
            $defaultSubsidiaryId = \trim(
                $this->scopeConfig->getValue('firebear_importexport/netsuite/default_subsidiary_internal_id')
            );
            if (!empty($defaultSubsidiaryId)) {
                $subsidiaryInternalId = $defaultSubsidiaryId;
            }
        }
        if ($subsidiaryInternalId) {
            $subsidiary->internalId = $subsidiaryInternalId;
        }

        return $subsidiary;
    }

    /**
     * @return array
     */
    private function getNetsuitePriceLevelMapping()
    {
        $behaviorData = $this->getBehaviorData();
        $netsuitePriceLevelMapping = [];
        if (!empty($behaviorData['netsuite_customer_price_level_map'])) {
            foreach ($behaviorData['netsuite_customer_price_level_map'] as $key => $data) {
                $netsuitePriceLevelMapping[$data['behavior_field_netsuite_customer_price_level_map_customer_group']] =
                    $data['behavior_field_netsuite_customer_price_level_map_price_level_id'];
            }
        }
        return $netsuitePriceLevelMapping;
    }

    /**
     * @param $data
     * @param $customer
     */
    protected function addCustomField($data, $customer) {
        $customizationTypeName = \NetSuite\Classes\GetCustomizationType::entityCustomField;
        $netsuiteCustomFieldsMapping = $this->getNetsuiteCustomFieldsMapping($customizationTypeName);
        if (!empty($netsuiteCustomFieldsMapping)) {
            $customFieldList = new \NetSuite\Classes\CustomFieldList();
            foreach ($netsuiteCustomFieldsMapping as $exportAttribute => $systemAttribute) {
                if (isset($data[$systemAttribute]) || isset($this->customFieldReplaceData[$exportAttribute])) {
                    $customFieldValue = isset($this->customFieldReplaceData[$exportAttribute]) ?
                        $this->customFieldReplaceData[$exportAttribute] : $data[$systemAttribute];
                    if (isset($this->customFieldOptionData[$exportAttribute])
                        && isset($this->customFieldOptionData[$exportAttribute][$customFieldValue])
                    ) {
                        $customFieldValue = $this->customFieldOptionData[$exportAttribute][$customFieldValue];
                    }
                    $custentityField = new \NetSuite\Classes\StringCustomFieldRef();
                    $custentityField->value = $customFieldValue;
                    $custentityField->scriptId = $exportAttribute;
                    $customFieldList->customField[] = $custentityField;
                }
            }

            if (!empty($customFieldList->customField)) {
                $customer->customFieldList = $customFieldList;
            }
        }
        return $customer;
    }
}
