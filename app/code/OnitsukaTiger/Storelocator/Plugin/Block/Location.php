<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Storelocator
 */

namespace OnitsukaTiger\Storelocator\Plugin\Block;

class Location
{
    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $jsonEncoder;

    public function __construct(
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\View\Element\Template\Context $context
    ) {
        $this->jsonEncoder = $jsonEncoder;
        $this->_storeManager = $context->getStoreManager();
    }

    /**
     * @param \Amasty\Storelocator\Block\Location $subject
     * @param $result
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterGetJsonLocations(\Amasty\Storelocator\Block\Location $subject, $result)
    {
        $locationArray = [];
        $locationArray['items'] = [];
        foreach ($subject->getLocationCollection() as $location) {
            if ($location->getMarkerImg()) {
                $location->setData('marker_url', $location->getMarkerMediaUrl());
            }
            $locationArray['items'][] = $location->getData();
        }
        $locationArray['totalRecords'] = count($locationArray['items']);
        $store = $this->_storeManager->getStore(true)->getId();
        $locationArray['currentStoreId'] = $store;

        $locationArray['block'] = $subject->getLeftBlockHtml();

        return $this->jsonEncoder->encode($locationArray);
    }
}
