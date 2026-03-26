<?php

/** phpcs:ignoreFile */

namespace OnitsukaTigerKorea\Customer\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Controller\Adminhtml\Index as CustomerIndex;
use Magento\Customer\Helper\View;
use Magento\Customer\Model\Address\Mapper;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\DataObjectFactory as ObjectFactory;
use Magento\Framework\Math\Random;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Newsletter\Model\SubscriberFactory;
use Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterface;
use Seoulwebdesign\KakaoSync\Model\AccessTokenRepository;
use Seoulwebdesign\KakaoSync\Service\Kakao;
use Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterfaceFactory;
use Seoulwebdesign\KakaoSync\Model\ResourceModel\AccessToken as ResourceAccessToken;

class Delete extends CustomerIndex implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Customer::delete';

    private AccessTokenRepository $accessTokenRepository;
    private Kakao $kakaoService;
    /**
     * @var AccessTokenInterfaceFactory
     */
    protected $accessTokenFactory;

    /**
     * @var ResourceAccessToken
     */
    protected $resource;

    public function __construct(
        Context                                 $context,
        Registry                                $coreRegistry,
        FileFactory                             $fileFactory,
        CustomerFactory                         $customerFactory,
        AddressFactory                          $addressFactory,
        FormFactory                             $formFactory,
        SubscriberFactory                       $subscriberFactory,
        View                                    $viewHelper,
        Random                                  $random,
        CustomerRepositoryInterface             $customerRepository,
        ExtensibleDataObjectConverter           $extensibleDataObjectConverter,
        Mapper                                  $addressMapper,
        AccountManagementInterface              $customerAccountManagement,
        AddressRepositoryInterface              $addressRepository,
        CustomerInterfaceFactory                $customerDataFactory,
        AddressInterfaceFactory                 $addressDataFactory,
        \Magento\Customer\Model\Customer\Mapper $customerMapper,
        DataObjectProcessor                     $dataObjectProcessor,
        DataObjectHelper                        $dataObjectHelper,
        ObjectFactory                           $objectFactory,
        \Magento\Framework\View\LayoutFactory   $layoutFactory,
        LayoutFactory                           $resultLayoutFactory,
        PageFactory                             $resultPageFactory,
        ForwardFactory                          $resultForwardFactory,
        JsonFactory                             $resultJsonFactory,
        AccessTokenRepository                   $accessTokenRepository,
        Kakao                                   $kakaoService,
        AccessTokenInterfaceFactory $accessTokenFactory,
        ResourceAccessToken $resource
    ) {
        parent::__construct(
            $context,
            $coreRegistry,
            $fileFactory,
            $customerFactory,
            $addressFactory,
            $formFactory,
            $subscriberFactory,
            $viewHelper,
            $random,
            $customerRepository,
            $extensibleDataObjectConverter,
            $addressMapper,
            $customerAccountManagement,
            $addressRepository,
            $customerDataFactory,
            $addressDataFactory,
            $customerMapper,
            $dataObjectProcessor,
            $dataObjectHelper,
            $objectFactory,
            $layoutFactory,
            $resultLayoutFactory,
            $resultPageFactory,
            $resultForwardFactory,
            $resultJsonFactory
        );
        $this->accessTokenRepository = $accessTokenRepository;
        $this->kakaoService = $kakaoService;
        $this->accessTokenFactory = $accessTokenFactory;
        $this->resource = $resource;
    }

    /**
     * Delete customer action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $formKeyIsValid = $this->_formKeyValidator->validate($this->getRequest());
        $isPost = $this->getRequest()->isPost();
        if (!$formKeyIsValid || !$isPost) {
            $this->messageManager->addErrorMessage(__('Customer could not be deleted.'));
            return $resultRedirect->setPath('customer/index');
        }

        $customerId = $this->initCurrentCustomer();
        if (!empty($customerId)) {
            try {
                $customer = $this->_customerRepository->getById($customerId);
                $storeId = $customer->getStoreId();
                $websiteId = $customer->getWebsiteId();
                if ($websiteId == 4 || $storeId == 5) {
                    $customerToken = $this->getByCustomerId($customerId);
                    if ($customerToken) {
                        $this->kakaoService->unlink($customerToken->getAccessToken());
                    }
                    $this->messageManager->addWarningMessage(__('You can not delete this customer.'));
                } else {
                    $this->_customerRepository->deleteById($customerId);
                    $this->messageManager->addSuccessMessage(__('You deleted the customer.'));
                }

            } catch (\Exception $exception) {
                $this->messageManager->addErrorMessage($exception->getMessage());
            }
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('customer/index');
    }

    /**
     * @param $customerId
     * @return AccessTokenInterface|string
     */
    public function getByCustomerId($customerId)
    {
        $accessToken = $this->accessTokenFactory->create();
        $this->resource->load($accessToken, $customerId, AccessTokenInterface::CUSTOMER_ID);
        if (!$accessToken->getId()) {
            return "";
        }
        return $accessToken;
    }
}
