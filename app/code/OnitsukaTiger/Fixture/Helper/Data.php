<?php

namespace OnitsukaTiger\Fixture\Helper;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\StoreRepositoryInterface;

/**
 * Class Data
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var EncoderInterface
     */
    private $encoder;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var StoreRepositoryInterface
     */
    protected $storeRepository;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\View\Element\Template\Context $contextHtml
     * @param EncoderInterface $encoder
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\View\Element\Template\Context $contextHtml,
        StoreRepositoryInterface $storeRepository,
        EncoderInterface $encoder,
        UrlInterface $urlBuilder
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->_storeManager = $contextHtml->getStoreManager();
        $this->encoder = $encoder;
        $this->storeRepository = $storeRepository;
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context);
    }

    /**
     * @param $config_path
     * @return mixed
     */
    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrentStore()
    {
        return $this->_storeManager->getStore();
    }
    /**
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreData()
    {
        $storeManagerDataList = $this->_storeManager->getStores();
        $options = [];
        foreach ($storeManagerDataList as $key => $value) {
            $url = $this->getTargetStoreRedirectUrl($value);
            $options[] = [
                'label' => $value['name'],
                'url' => $url,
                'value' => $key
            ];
        }
        return $options;
    }

    /**
     * @param $code
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreDataByCode($code)
    {
        $fromStore = $this->storeRepository->get($code);
        $url = $this->getTargetStoreRedirectUrl($fromStore);
        $options = [
            'label' => $fromStore->getName(),
            'code' => $fromStore->getCode(),
            'url' => $url
        ];
        return $options;
    }
    /**
     * @param $code
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreDataByCodeLanguage($code)
    {
        $fromStore = $this->storeRepository->get($code);
        $url = $this->getTargetStoreRedirectUrl($fromStore,true);
        $options = [
            'label' => $fromStore->getName(),
            'code' => $fromStore->getCode(),
            'url' => $url
        ];
        return $options;
    }

    /**
     * @param $store
     * @param bool $language
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getTargetStoreRedirectUrl($store, $language = false): string
    {
        $params =  [
            '___store' => $store->getCode(),
            '___from_store' => $this->getCurrentStore()->getCode(),
            ActionInterface::PARAM_NAME_URL_ENCODED => '',
        ];
        if($language){
            $params[ActionInterface::PARAM_NAME_URL_ENCODED] = $this->encoder->encode(
                $store->getCurrentUrl(true)
            );
        }
        return $this->urlBuilder->getUrl(
            'stores/store/redirect',
            $params
        );
    }
}
