<?php


namespace Gdx\AdminTheme\Helper;

use Gdx\AdminTheme\Model\Config\Backend\Image;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const TESTLABEL_COLOR = 'gdx_admintheme/env_label/color';

    const TESTLABEL_TEXT = 'gdx_admintheme/env_label/text';

    const TESTLABEL_TEXT_COLOR = 'gdx_admintheme/env_label/text_color';
    const ADMIN_LOGO_IMAGE = 'gdx_admintheme/logos/admin_logo_image';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * @return mixed
     */
    public function getTestLabelColor() {
        return $this->scopeConfig->getValue(self::TESTLABEL_COLOR);
    }

    /**
     * @return mixed
     */
    public function getTestLabelText() {
        return $this->scopeConfig->getValue(self::TESTLABEL_TEXT);
    }

    /**
     * @return mixed
     */
    public function getTestLabelTextColor() {
        return $this->scopeConfig->getValue(self::TESTLABEL_TEXT_COLOR);
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getAdminLoginFormLogoImage()
    {
        $imageUrl = null;
        if ($this->scopeConfig->getValue(self::ADMIN_LOGO_IMAGE)) {
            $imageUrl = $this->getBaseMediaUrl() . '/' . Image::UPLOAD_DIR . '/' . $this->scopeConfig->getValue(self::ADMIN_LOGO_IMAGE);
        }
        return $imageUrl;
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getBaseMediaUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

}
