<?php
namespace WeltPixel\GA4\Observer\ServerSide\Events;

use Magento\Framework\Event\ObserverInterface;

class SignupObserver implements ObserverInterface
{
    /**
     * @var \WeltPixel\GA4\Helper\ServerSideTracking
     */
    protected $ga4Helper;

    /** @var \WeltPixel\GA4\Api\ServerSide\Events\SignupBuilderInterface */
    protected $signupBuilder;

    /** @var \WeltPixel\GA4\Model\ServerSide\Api */
    protected $ga4ServerSideApi;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \WeltPixel\GA4\Helper\RedditPixelTracking
     */
    protected $redditPixelTrackingHelper;

    /**
     * @var \WeltPixel\GA4\Helper\BingPixelTracking
     */
    protected $bingPixelTrackingHelper;

    /**
     * @var \WeltPixel\GA4\Helper\KlaviyoPixelTracking
     */
    protected $klaviyoPixelTrackingHelper;

    /**
     * @var \WeltPixel\GA4\Helper\PinterestPixelTracking
     */
    protected $pinterestPixelTrackingHelper;

    /**
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper
     * @param \WeltPixel\GA4\Api\ServerSide\Events\SignupBuilderInterface $signupBuilder
     * @param \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \WeltPixel\GA4\Helper\RedditPixelTracking $redditPixelTrackingHelper
     * @param \WeltPixel\GA4\Helper\BingPixelTracking $bingPixelTrackingHelper
     * @param \WeltPixel\GA4\Helper\KlaviyoPixelTracking $klaviyoPixelTrackingHelper
     * @param \WeltPixel\GA4\Helper\PinterestPixelTracking $pinterestPixelTrackingHelper
     */
    public function __construct(
        \WeltPixel\GA4\Helper\ServerSideTracking $ga4Helper,
        \WeltPixel\GA4\Api\ServerSide\Events\SignupBuilderInterface $signupBuilder,
        \WeltPixel\GA4\Model\ServerSide\Api $ga4ServerSideApi,
        \Magento\Customer\Model\Session $customerSession,
        \WeltPixel\GA4\Helper\RedditPixelTracking $redditPixelTrackingHelper,
        \WeltPixel\GA4\Helper\BingPixelTracking $bingPixelTrackingHelper,
        \WeltPixel\GA4\Helper\KlaviyoPixelTracking $klaviyoPixelTrackingHelper,
        \WeltPixel\GA4\Helper\PinterestPixelTracking $pinterestPixelTrackingHelper
    )
    {
        $this->ga4Helper = $ga4Helper;
        $this->signupBuilder = $signupBuilder;
        $this->ga4ServerSideApi = $ga4ServerSideApi;
        $this->customerSession = $customerSession;
        $this->redditPixelTrackingHelper = $redditPixelTrackingHelper;
        $this->bingPixelTrackingHelper = $bingPixelTrackingHelper;
        $this->klaviyoPixelTrackingHelper = $klaviyoPixelTrackingHelper;
        $this->pinterestPixelTrackingHelper = $pinterestPixelTrackingHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customerDataObject = $observer->getData('customer_data_object') ?? false;
        $origCustomerDataObject = $observer->getData('orig_customer_data_object') ?? false;
        if ($this->ga4Helper->isServerSideTrakingEnabled() && $this->ga4Helper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_SIGNUP)) {
            if (!$origCustomerDataObject) {
                $customerId = $customerDataObject->getId();
                $signupEvent = $this->signupBuilder->getSignupEvent($customerId);
                $this->ga4ServerSideApi->pushSignupEvent($signupEvent);
            }
        }

        if (!($this->ga4Helper->isServerSideTrakingEnabled() && $this->ga4Helper->shouldEventBeTracked(\WeltPixel\GA4\Model\Config\Source\ServerSide\TrackingEvents::EVENT_SIGNUP)
            && $this->ga4Helper->isDataLayerEventDisabled())) {
            if (!$origCustomerDataObject) {
                $this->customerSession->setGA4SignupData([
                    'event' => 'sign_up',
                    'ecommerce' => [
                        'method' => 'Magento',
                    ]
                ]);
            }
        }

        if ($this->redditPixelTrackingHelper->isRedditPixelTrackingEnabled() && $this->redditPixelTrackingHelper->shouldRedditPixelEventBeTracked(\WeltPixel\GA4\Model\Config\Source\RedditPixel\TrackingEvents::EVENT_SIGN_UP)) {
            if (!$origCustomerDataObject) {
                $this->customerSession->setRedditPixelSignupData([
                    'track' => 'track',
                    'eventName' => \WeltPixel\GA4\Model\Config\Source\RedditPixel\TrackingEvents::EVENT_SIGN_UP,
                    'eventData' => [
                        'conversionId' => $this->redditPixelTrackingHelper->getSignUpEventConversionID()
                    ]
                ]);
            }
        }

        if ($this->bingPixelTrackingHelper->isBingPixelTrackingEnabled() && $this->bingPixelTrackingHelper->shouldBingPixelEventBeTracked(\WeltPixel\GA4\Model\Config\Source\BingPixel\TrackingEvents::EVENT_SIGNUP)) {
            if (!$origCustomerDataObject) {
                $this->customerSession->setBingPixelSignupData([
                    'event' => 'event',
                    'eventName' => \WeltPixel\GA4\Model\Config\Source\BingPixel\TrackingEvents::EVENT_SIGNUP,
                    'eventData' => [
                        'ecomm_pagetype' => 'other',
                        'custom_parameters' => [
                            'mid' => $this->bingPixelTrackingHelper->getSignUpEventMID(),
                            'signup_method' => 'Magento'
                        ],
                        'mid' => $this->bingPixelTrackingHelper->getSignUpEventMID()
                    ]
                ]);
            }
        }

        if ($this->klaviyoPixelTrackingHelper->isKlaviyoPixelTrackingEnabled() && $this->klaviyoPixelTrackingHelper->shouldKlaviyoPixelEventBeTracked(\WeltPixel\GA4\Model\Config\Source\KlaviyoPixel\TrackingEvents::EVENT_CREATED_ACCOUNT)) {
            $this->customerSession->setKlaviyoPixelSignupData([
                'eventName' => $this->klaviyoPixelTrackingHelper->getKlaviyoEventName(\WeltPixel\GA4\Model\Config\Source\KlaviyoPixel\TrackingEvents::EVENT_CREATED_ACCOUNT),
                'eventData' => [
                    '$event_id' => $this->klaviyoPixelTrackingHelper->getSignUpEventID(),
                    '$email' => $customerDataObject->getEmail(),
                    '$first_name' => $customerDataObject->getFirstname(),
                    '$last_name' => $customerDataObject->getLastname(),
                    'AccountID' => $customerDataObject->getId(),
                    'AccountType' => 'Customer',
                    'AccountCreationMethod' => 'Email',
                    'AccountCreationPlatform' => 'Magento Website'
                ]
            ]);
        }

        if ($this->pinterestPixelTrackingHelper->isPinterestPixelTrackingEnabled() && $this->pinterestPixelTrackingHelper->shouldPinterestPixelEventBeTracked(\WeltPixel\GA4\Model\Config\Source\PinterestPixel\TrackingEvents::EVENT_SIGN_UP)) {
            if (!$origCustomerDataObject) {
                $this->customerSession->setPinterestPixelSignupData([
                    'track' => 'track',
                    'eventName' => \WeltPixel\GA4\Model\Config\Source\PinterestPixel\TrackingEvents::EVENT_SIGN_UP,
                    'eventData' => [
                        'event_id' => $this->pinterestPixelTrackingHelper->getSignUpEventUID()
                    ]
                ]);
            }
        }


        return $this;
    }
}
