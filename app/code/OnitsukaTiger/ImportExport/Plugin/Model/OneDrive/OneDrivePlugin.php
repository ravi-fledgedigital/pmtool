<?php
declare(strict_types=1);

namespace OnitsukaTiger\ImportExport\Plugin\Model\OneDrive;

use Firebear\ImportExport\Model\OneDrive\OneDrive;

/**
 * class OneDrivePlugin
 */
class OneDrivePlugin
{
    /**
     * @var \Magento\Framework\App\Config\Storage\WriterInterface
     */
    protected $writer;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\App\Cache\TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @param \Magento\Framework\App\Config\Storage\WriterInterface $writer
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     */
    public function __construct(
        \Magento\Framework\App\Config\Storage\WriterInterface $writer,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
    ) {
        $this->writer = $writer;
        $this->_scopeConfig = $scopeConfig;
        $this->cacheTypeList = $cacheTypeList;
    }

    /**
     * @param OneDrive $subject
     * @param $result
     * @return mixed
     */
    public function afterGetRedirectUri(OneDrive $subject, $result)
    {
        $urlRedirect = $this->_scopeConfig->getValue('firebear_importexport/onedrive/redirect_uri');
        if ($urlRedirect) {
            return $this->_scopeConfig->getValue('firebear_importexport/onedrive/redirect_uri');
        }
        return $result;
    }

    /**
     * @param OneDrive $subject
     * @param callable $proceed
     * @param $providedState
     * @return bool
     */
    public function aroundCheckAuthState(OneDrive $subject, callable $proceed, $providedState)
    {
        $subject->getAuthState();
        return true;
    }
}