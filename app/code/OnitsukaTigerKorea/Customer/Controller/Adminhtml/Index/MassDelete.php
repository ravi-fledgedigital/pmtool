<?php
/** phpcs:ignoreFile */

namespace OnitsukaTigerKorea\Customer\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Controller\Adminhtml\Index\AbstractMassAction;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Ui\Component\MassAction\Filter;
use Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterface;
use Seoulwebdesign\KakaoSync\Api\Data\AccessTokenInterfaceFactory;
use Seoulwebdesign\KakaoSync\Model\AccessTokenRepository;
use Seoulwebdesign\KakaoSync\Service\Kakao;
use Seoulwebdesign\KakaoSync\Model\ResourceModel\AccessToken as ResourceAccessToken;

class MassDelete extends AbstractMassAction implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Customer::delete';

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var AccessTokenRepository
     */
    private AccessTokenRepository $accessTokenRepository;

    /**
     * @var Kakao
     */
    private Kakao $kakaoService;

    /**
     * @var AccessTokenInterfaceFactory
     */
    protected $accessTokenFactory;

    /**
     * @var ResourceAccessToken
     */
    protected $resource;


    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        Context                                               $context,
        Filter                                                $filter,
        CollectionFactory                                     $collectionFactory,
        CustomerRepositoryInterface                           $customerRepository,
        AccessTokenRepository $accessTokenRepository,
        Kakao                                                 $kakaoService,
        AccessTokenInterfaceFactory $accessTokenFactory,
        ResourceAccessToken $resource
    ) {
        parent::__construct($context, $filter, $collectionFactory);
        $this->customerRepository = $customerRepository;
        $this->accessTokenRepository = $accessTokenRepository;
        $this->kakaoService = $kakaoService;
        $this->accessTokenFactory = $accessTokenFactory;
        $this->resource = $resource;
    }

    /**
     * @inheritdoc
     */
    protected function massAction(AbstractCollection $collection)
    {
        $customersDeleted = 0;
        $customerNotDeleted = 0;
        foreach ($collection->getAllIds() as $customerId) {
            $customer = $this->customerRepository->getById($customerId);
            $storeId = $customer->getStoreId();
            $websiteId = $customer->getWebsiteId();
            if ($websiteId == 4 || $storeId == 5) {
                $customerNotDeleted++;
                $customerToken = $this->getByCustomerId($customerId);
                if (!empty($customerToken)) {
                    $this->kakaoService->unlink($customerToken->getAccessToken());
                }
            } else {
                $this->customerRepository->deleteById($customerId);
                $customersDeleted++;
            }
        }

        if ($customersDeleted) {
            $this->messageManager->addSuccessMessage(__('A total of %1 record(s) were deleted.', $customersDeleted));
        }
        if ($customerNotDeleted) {
            $this->messageManager->addWarningMessage(__('A total of %1 record(s) were not deleted.', $customerNotDeleted));
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath($this->getComponentRefererUrl());

        return $resultRedirect;
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
