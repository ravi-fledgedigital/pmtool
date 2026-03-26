<?php

namespace OnitsukaTigerKorea\Customer\Controller\Adminhtml\Index;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\Address\Mapper;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\DataObjectFactory as ObjectFactory;
use Magento\Framework\Message\Error;
use Magento\Store\Model\StoreManagerInterface;

class Validate extends \Magento\Customer\Controller\Adminhtml\Index\Validate
{
    /**
     * @var
     */
    private $storeManager;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\AddressFactory $addressFactory
     * @param \Magento\Customer\Model\Metadata\FormFactory $formFactory
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Magento\Customer\Helper\View $viewHelper
     * @param \Magento\Framework\Math\Random $random
     * @param CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param Mapper $addressMapper
     * @param AccountManagementInterface $customerAccountManagement
     * @param AddressRepositoryInterface $addressRepository
     * @param CustomerInterfaceFactory $customerDataFactory
     * @param AddressInterfaceFactory $addressDataFactory
     * @param \Magento\Customer\Model\Customer\Mapper $customerMapper
     * @param \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor
     * @param DataObjectHelper $dataObjectHelper
     * @param ObjectFactory $objectFactory
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param StoreManagerInterface|null $storeManager
     */
    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\Registry $coreRegistry, \Magento\Framework\App\Response\Http\FileFactory $fileFactory, \Magento\Customer\Model\CustomerFactory $customerFactory, \Magento\Customer\Model\AddressFactory $addressFactory, \Magento\Customer\Model\Metadata\FormFactory $formFactory, \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory, \Magento\Customer\Helper\View $viewHelper, \Magento\Framework\Math\Random $random, CustomerRepositoryInterface $customerRepository, \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter, Mapper $addressMapper, AccountManagementInterface $customerAccountManagement, AddressRepositoryInterface $addressRepository, CustomerInterfaceFactory $customerDataFactory, AddressInterfaceFactory $addressDataFactory, \Magento\Customer\Model\Customer\Mapper $customerMapper, \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor, DataObjectHelper $dataObjectHelper, ObjectFactory $objectFactory, \Magento\Framework\View\LayoutFactory $layoutFactory, \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory, \Magento\Framework\View\Result\PageFactory $resultPageFactory, \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory, \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory, StoreManagerInterface $storeManager)
    {
        parent::__construct($context, $coreRegistry, $fileFactory, $customerFactory, $addressFactory, $formFactory, $subscriberFactory, $viewHelper, $random, $customerRepository, $extensibleDataObjectConverter, $addressMapper, $customerAccountManagement, $addressRepository, $customerDataFactory, $addressDataFactory, $customerMapper, $dataObjectProcessor, $dataObjectHelper, $objectFactory, $layoutFactory, $resultLayoutFactory, $resultPageFactory, $resultForwardFactory, $resultJsonFactory, $storeManager);
        $this->storeManager = $storeManager;
    }

    protected function _validateCustomer($response)
    {
        $customer = null;
        $errors = [];

        try {
            /** @var CustomerInterface $customer */
            $customer = $this->customerDataFactory->create();

            $customerForm = $this->_formFactory->create(
                'customer',
                'adminhtml_customer',
                [],
                true
            );
            $customerForm->setInvisibleIgnored(true);

            $data = $customerForm->extractData($this->getRequest(), 'customer');
            $postData = $this->getRequest()->getParam('customer');
            if (isset($postData['gender'])) {
                $data['gender'] = $postData['gender'];
            }
            if ($customer->getWebsiteId()) {
                unset($data['website_id']);
            }

            $this->dataObjectHelper->populateWithArray(
                $customer,
                $data,
                \Magento\Customer\Api\Data\CustomerInterface::class
            );
            $submittedData = $postData;
            if (isset($submittedData['entity_id'])) {
                $entity_id = $submittedData['entity_id'];
                $customer->setId($entity_id);
            }
            if (isset($data['website_id']) && is_numeric($data['website_id'])) {
                $website = $this->storeManager->getWebsite($data['website_id']);
                $storeId = current($website->getStoreIds());
                $this->storeManager->setCurrentStore($storeId);
            }
            $errors = $this->customerAccountManagement->validate($customer)->getMessages();
        } catch (\Magento\Framework\Validator\Exception $exception) {
            /* @var $error Error */
            foreach ($exception->getMessages(\Magento\Framework\Message\MessageInterface::TYPE_ERROR) as $error) {
                $errors[] = $error->getText();
            }
        }

        if ($errors) {
            $messages = $response->hasMessages() ? $response->getMessages() : [];
            foreach ($errors as $error) {
                $messages[] = $error;
            }
            $response->setMessages($messages);
            $response->setError(1);
        }

        return $customer;
    }
}
