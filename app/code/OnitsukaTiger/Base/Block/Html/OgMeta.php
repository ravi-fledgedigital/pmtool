<?php

declare(strict_types=1);

namespace OnitsukaTiger\Base\Block\Html;

use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;

/**
 * Open Graph Meta Block
 *
 * Provides methods to generate Open Graph meta tags for social media sharing
 */
class OgMeta extends Template
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    /**
     * Get media URL for the current store
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getMediaUrl(): string
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
    }

    /**
     * Get current page URL
     *
     * @return string
     */
    public function getCurrentUrl(): string
    {
        return $this->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true]);
    }

    /**
     * Get page title from page configuration
     *
     * @return string
     */
    public function getPageTitle(): string
    {
        return (string) $this->pageConfig->getTitle()->get();
    }

    /**
     * Get page description from page configuration
     *
     * @return string
     */
    public function getPageDescription(): string
    {
        return (string) $this->pageConfig->getDescription();
    }
}
