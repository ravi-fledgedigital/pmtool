<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Store Locator for Magento 2
 */

namespace Amasty\Storelocator\Block\View;

use Amasty\Base\Model\Serializer;
use Amasty\Storelocator\Model\ConfigProvider;
use Amasty\Storelocator\Model\ImageProcessor;
use Amasty\Storelocator\Model\Location as locationModel;
use Amasty\Storelocator\Model\ResourceModel\Gallery\Collection;
use Amasty\Storelocator\Model\Review as reviewModel;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Location front block.
 */
class Location extends Template implements IdentityInterface
{
    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var ConfigProvider
     */
    public $configProvider;

    /**
     * @var locationModel
     */
    private $locationModel;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * @var \Amasty\Storelocator\Helper\Data
     */
    public $dataHelper;

    /**
     * @var Collection
     */
    private $galleryCollection;

    /**
     * @var ImageProcessor
     */
    private $imageProcessor;

    /**
     * @var CountryFactory
     */
    private $countryFactory;

    /**
     * @var RegionFactory
     */
    private $regionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var array
     */
    private $identities = [];

    public function __construct(
        Template\Context $context,
        Registry $coreRegistry,
        ConfigProvider $configProvider,
        locationModel $locationModel,
        Collection $galleryCollection,
        ImageProcessor $imageProcessor,
        Serializer $serializer,
        CountryFactory $countryFactory,
        RegionFactory $regionFactory,
        \Amasty\Storelocator\Helper\Data $dataHelper,
        ?StoreManagerInterface $storeManager = null,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->coreRegistry = $coreRegistry;
        $this->configProvider = $configProvider;
        $this->locationModel = $locationModel;
        $this->serializer = $serializer;
        $this->galleryCollection = $galleryCollection;
        $this->imageProcessor = $imageProcessor;
        $this->dataHelper = $dataHelper;
        $this->countryFactory = $countryFactory;
        $this->regionFactory = $regionFactory;
        $this->storeManager = $storeManager
            ?? ObjectManager::getInstance()->get(StoreManagerInterface::class);
    }

    public function getCacheLifetime()
    {
        return null;
    }

    /**
     * @return locationModel|bool
     */
    public function getCurrentLocation()
    {
        if ($this->getLocationId()) {
            try {
                $this->locationModel->load($this->getLocationId());
                $this->locationModel->setSchedule($this->serializer->unserialize($this->locationModel->getSchedule()));

                return $this->locationModel;
                //phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
            } catch (\Exception $e) {
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function getLocationGallery()
    {
        $locationId = $this->getLocationId();
        $locationImages = $this->galleryCollection->getImagesByLocation($locationId);
        $result = [];

        foreach ($locationImages as $image) {
            array_push(
                $result,
                [
                    'name'    => $image->getData('image_name'),
                    'is_base' => (bool)$image->getData('is_base'),
                    'path'    => $this->imageProcessor->getImageUrl(
                        [ImageProcessor::AMLOCATOR_GALLERY_MEDIA_PATH, $locationId, $image->getData('image_name')]
                    )
                ]
            );
        }

        return $result;
    }

    /**
     * @return int
     */
    public function getLocationId()
    {
        if (!$this->hasData('location_id')) {
            $this->setData('location_id', $this->coreRegistry->registry('amlocator_current_location_id'));
        }

        return (int)$this->getData('location_id');
    }

    /**
     * Add metadata to page
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $location = $this->getCurrentLocation();

        if ($location) {
            $locationMetaTitle = $location->getMetaTitle();
            if ($locationMetaTitle) {
                $this->pageConfig->getTitle()->set($locationMetaTitle);
                $this->pageConfig->setMetaTitle($locationMetaTitle);
            }
            /** @var \Magento\Theme\Block\Html\Title $headingBlock */
            if ($headingBlock = $this->getLayout()->getBlock('page.main.title')) {
                $headingBlock->setPageTitle($location->getName());
            }
            if ($description = $location->getMetaDescription()) {
                $this->pageConfig->setDescription($description);
            }
            if ($metaRobots = $location->getMetaRobots()) {
                $this->pageConfig->setRobots($metaRobots);
            }
            if ($canonical = $location->getCanonicalUrl()) {
                $this->pageConfig->addRemotePageAsset(
                    $canonical,
                    'canonical',
                    ['attributes' => ['rel' => 'canonical']]
                );
            }
        }

        $breadcrumbsBlock = $this->getLayout()->getBlock('breadcrumbs');

        if ($location && $breadcrumbsBlock) {
            $breadcrumbsBlock->addCrumb(
                'storelocator',
                [
                    'label' => $this->configProvider->getLabel(),
                    'title' => $this->configProvider->getLabel(),
                    'link' => $this->_urlBuilder->getUrl($this->configProvider->getUrl())
                ]
            );
            $breadcrumbsBlock->addCrumb(
                'location_page',
                [
                    'label' => $location->getName(),
                    'title' => $location->getName()
                ]
            );
        }

        return parent::_prepareLayout();
    }

    /**
     * Return identifiers for produced content
     *
     * @return array
     */
    public function getIdentities()
    {
        if (empty($this->identities)) {
            $this->identities = [
                locationModel::CACHE_TAG . '_s_' . $this->storeManager->getStore()->getId()
                    . '_lid_' . $this->getLocationId(),
                locationModel::CACHE_TAG . '_' . $this->getLocationId(),
                reviewModel::CACHE_TAG
            ];
        }

        return $this->identities;
    }

    /**
     * @param array $identities
     * @return $this
     */
    public function setIdentities(array $identities): self
    {
        $this->identities = $identities;
        return $this;
    }

    /**
     * @return array
     */
    public function getCacheKeyInfo()
    {
        return parent::getCacheKeyInfo() + ['l_id' => $this->getLocationId()];
    }

    /**
     * @param string $code
     *
     * @return string
     */
    public function getLocationCountryName($code)
    {
        return $this->countryFactory->create()->loadByCode($code)->getName();
    }

    /**
     * @param $stateCode
     * @return string|null
     */
    public function getStateName($stateCode)
    {
        return $this->regionFactory->create()->load($stateCode)->getName();
    }

    public function generateMapId(): string
    {
        if (!$this->hasData('map_container_id')) {
            $this->setData('map_container_id', uniqid('amlocator-map-container'));
        }

        return (string)$this->getData('map_container_id');
    }
}
