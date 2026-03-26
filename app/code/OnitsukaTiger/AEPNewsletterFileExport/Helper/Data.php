<?php

namespace OnitsukaTiger\AEPNewsletterFileExport\Helper;

use OnitsukaTiger\AEPNewsletterFileExport\Model\Subscriber;
use Magento\Framework\UrlInterface;

/**
 * Newsletter Data Helper
 */
class Data
{
    /**
     * @var UrlInterface
     */
    protected UrlInterface $frontendUrlBuilder;

    /**
     * Constructor
     *
     * @param UrlInterface $frontendUrlBuilder
     */
    public function __construct(UrlInterface $frontendUrlBuilder)
    {
        $this->frontendUrlBuilder = $frontendUrlBuilder;
    }

    /**
     * Retrieve unsubscription url
     *
     * @param Subscriber $subscriber
     * @return string
     */
    public function getUnsubscribeUrl($subscriber)
    {
        return $this->frontendUrlBuilder->setScope(
            $subscriber->getStoreId()
        )->getUrl(
            'newsletter/subscriber/unsubscribe',
            ['id' => $subscriber->getId(), 'code' => $subscriber->getCode(), '_nosid' => true]
        );
    }
}
