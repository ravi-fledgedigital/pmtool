<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace OnitsukaTiger\OrderTracking\Helper;

use Magento\Framework\App as App;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use \Magento\Sales\Model\Order;
use Magento\Directory\Model\CountryFactory;

/**
 * OrderTracking module base helper
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Guest extends \Magento\Sales\Helper\Guest
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    protected $cookieMetadataFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * Cookie key for guest view
     */
    const COOKIE_NAME = 'guest-view';

    /**
     * Cookie path
     */
    const COOKIE_PATH = '/';

    /**
     * Cookie lifetime value
     */
    const COOKIE_LIFETIME = 600;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var string
     */
    private $inputExceptionMessage = 'You entered incorrect data. Please try again.';

    /**
     * @var CountryFactory
     */
    protected $countryFactory;

   public function __construct(
       App\Helper\Context $context,
       \Magento\Store\Model\StoreManagerInterface $storeManager,
       \Magento\Framework\Registry $coreRegistry,
       \Magento\Customer\Model\Session $customerSession,
       \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
       \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
       \Magento\Framework\Message\ManagerInterface $messageManager,
       \Magento\Sales\Model\OrderFactory $orderFactory,
       \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
       \Magento\Sales\Api\OrderRepositoryInterface $orderRepository = null,
       CountryFactory $countryFactory,
       \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteria = null
   )
   {
       $this->coreRegistry = $coreRegistry;
       $this->storeManager = $storeManager;
       $this->customerSession = $customerSession;
       $this->cookieManager = $cookieManager;
       $this->cookieMetadataFactory = $cookieMetadataFactory;
       $this->messageManager = $messageManager;
       $this->orderFactory = $orderFactory;
       $this->resultRedirectFactory = $resultRedirectFactory;
       $this->countryFactory = $countryFactory;
       $this->orderRepository = $orderRepository ?: \Magento\Framework\App\ObjectManager::getInstance()
           ->get(\Magento\Sales\Api\OrderRepositoryInterface::class);
       $this->searchCriteriaBuilder = $searchCriteria?: \Magento\Framework\App\ObjectManager::getInstance()
           ->get(\Magento\Framework\Api\SearchCriteriaBuilder::class);

       parent::__construct($context, $storeManager, $coreRegistry, $customerSession, $cookieManager, $cookieMetadataFactory, $messageManager, $orderFactory, $resultRedirectFactory, $orderRepository, $searchCriteria);
   }

    /**
     * Try to load valid order by $_POST or $_COOKIE
     *
     * @param App\RequestInterface $request
     * @return \Magento\Framework\Controller\Result\Redirect|bool
     * @throws \RuntimeException
     * @throws InputException
     * @throws CookieSizeLimitReachedException
     * @throws FailureToSendException
     */
    public function loadValidOrder(App\RequestInterface $request)
    {
        $post = $request->getPostValue();
        $fromCookie = $this->cookieManager->getCookie(self::COOKIE_NAME);
        if (empty($post) && !$fromCookie) {
            return false;
        }
        // It is unique place in the class that process exception and only InputException. It is need because by
        // input data we found order and one more InputException could be throws deeper in stack trace

        if(!empty($post)  && isset($post['oar_order_id']) && !$this->hasPostDataEmptyFields($post)){
            $order = $this->loadFromPost($post);
            if($order == false) {
                return false;
            }
        }else {
            $order = $this->loadFromCookie($fromCookie);
        }

        $this->coreRegistry->register('current_order', $order);
        return true;

    }

    /**
     * Get Breadcrumbs for current controller action
     *
     * @param \Magento\Framework\View\Result\Page $resultPage
     * @return void
     */
    public function getBreadcrumbs(\Magento\Framework\View\Result\Page $resultPage)
    {
        $breadcrumbs = $resultPage->getLayout()->getBlock('breadcrumbs');
        if (!$breadcrumbs) {
            return;
        }
        $breadcrumbs->addCrumb(
            'home',
            [
                'label' => __('Home'),
                'title' => __('Go to Home Page'),
                'link' => $this->storeManager->getStore()->getBaseUrl()
            ]
        );
        $breadcrumbs->addCrumb(
            'cms_page',
            ['label' => __('Track Your Order'), 'title' => __('Track Your Order')]
        );
    }

    /**
     * Set guest-view cookie
     *
     * @param string $cookieValue
     * @return void
     * @throws InputException
     * @throws CookieSizeLimitReachedException
     * @throws FailureToSendException
     */
    private function setGuestViewCookie($cookieValue)
    {
        $metadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setPath(self::COOKIE_PATH)
            ->setHttpOnly(true);
        $this->cookieManager->setPublicCookie(self::COOKIE_NAME, $cookieValue, $metadata);
    }

    /**
     * Load order from cookie
     *
     * @param string $fromCookie
     * @return Order
     * @throws InputException
     * @throws CookieSizeLimitReachedException
     * @throws FailureToSendException
     */
    private function loadFromCookie($fromCookie)
    {
        $cookieData = explode(':', base64_decode($fromCookie));
        $protectCode = isset($cookieData[0]) ? $cookieData[0] : null;
        $incrementId = isset($cookieData[1]) ? $cookieData[1] : null;
        if (!empty($protectCode) && !empty($incrementId)) {
            $order = $this->getOrderRecord($incrementId);
            if (hash_equals((string)$order->getProtectCode(), $protectCode)) {
                $this->setGuestViewCookie($fromCookie);
                return $order;
            }
        }
        throw new InputException(__($this->inputExceptionMessage));
    }

    /**
     * Load order data from post
     *
     * @param array $postData
     * @return Order
     * @throws InputException
     * @throws CookieSizeLimitReachedException
     * @throws FailureToSendException
     */
    private function loadFromPost(array $postData)
    {
        /** @var $order \Magento\Sales\Model\Order */
        $order = $this->getOrderRecord($postData['oar_order_id']);
        if($order == false) { return false;}
        if (!$this->compareStoredBillingDataWithInput($order, $postData)) {
           return false;
        }
        $toCookie = base64_encode($order->getProtectCode() . ':' . $postData['oar_order_id']);
        $this->setGuestViewCookie($toCookie);
        return $order;
    }

    /**
     * Check that billing data from the order and from the input are equal
     *
     * @param Order $order
     * @param array $postData
     * @return bool
     */
    private function compareStoredBillingDataWithInput(Order $order, array $postData)
    {
        $email = $postData['oar_email'];
        $billingAddress = $order->getBillingAddress();
        return (strtolower($email) === strtolower($billingAddress->getEmail()));
    }

    /**
     * Check post data for empty fields
     *
     * @param array $postData
     * @return bool
     */
    private function hasPostDataEmptyFields(array $postData)
    {
        return empty($postData['oar_order_id'])|| empty($this->storeManager->getStore()->getId())  || empty($postData['oar_email']);
    }

    /**
     * Get order by increment_id and store_id
     *
     * @param string $incrementId
     * @return \Magento\Sales\Api\Data\OrderInterface
     * @throws InputException
     */
    private function getOrderRecord($incrementId)
    {
        $records = $this->orderRepository->getList(
            $this->searchCriteriaBuilder
                ->addFilter('increment_id', $incrementId)
                ->addFilter('store_id', $this->storeManager->getStore()->getId())
                ->create()
        );

        $items = $records->getItems();
        if (empty($items)) {
            return false;
           // throw new InputException(__($this->inputExceptionMessage));
        }

        return array_shift($items);
    }
    /**
     * Get country name by $countryCode
     *
     * Using \Magento\Directory\Model\Country to get country name by $countryCode
     *
     * @param string $countryCode
     * @return string
     * @since 102.0.1
     */
    public function getCountryByCode(string $countryCode): string
    {
        /** @var \Magento\Directory\Model\Country $country */
        $country = $this->countryFactory->create();
        return $country->loadByCode($countryCode)->getName();
    }
}
