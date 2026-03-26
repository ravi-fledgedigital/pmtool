<?php
namespace WeltPixel\GA4\Model\ServerSide;

class JsonBuilder extends \Magento\Framework\Model\AbstractModel
{

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $fileSystem;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $file;

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $cache;

    /**
     * @var \Magento\Framework\App\DeploymentConfig
     */
    protected $deploymentConfig;

    /**
     * @var \WeltPixel\GA4\Helper\ServerSideTracking
     */
    protected $serverSideTrackingHelper;

    /** @var string  */
    const CACHE_PATH = 'ga4_cache';

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Filesystem $fileSystem
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     * @param \WeltPixel\GA4\Helper\ServerSideTracking $serverSideTrackingHelper
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Filesystem $fileSystem,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \WeltPixel\GA4\Helper\ServerSideTracking $serverSideTrackingHelper
    )
    {
        parent::__construct($context, $registry);
        $this->fileSystem = $fileSystem;
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->cache = $cache;
        $this->deploymentConfig = $deploymentConfig;
        $this->serverSideTrackingHelper = $serverSideTrackingHelper;
    }


    /**
     * Check if Redis is enabled for frontend cache
     * @return bool
     */
    private function isRedisEnabled()
    {
        $useRedisCache = $this->serverSideTrackingHelper->useRedisCache();
        if (!$useRedisCache) {
            return false;
        }
        try {
            $defaultBackend = $this->deploymentConfig->get('cache/frontend/default/backend');
            if ($this->isRedisBackend($defaultBackend)) {
                return true;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if the backend is Redis
     * @param string|null $backend
     * @return bool
     */
    private function isRedisBackend($backend)
    {
        if (!$backend) {
            return false;
        }

        $redisBackends = [
            'Magento\Framework\Cache\Backend\Redis',
            'Cm_Cache_Backend_Redis',
            'Credis_Client'
        ];

        return in_array($backend, $redisBackends, true);
    }

    /**
     * Save content to cache (Redis or filesystem fallback)
     * @param $content
     * @return false|string|null
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function saveToFile($content)
    {
        if (empty($content)) {
            return null;
        }

        $fileHash = hash('sha1', $content);
        $cacheKey = 'weltpixel_ga4_' . $fileHash;

        try {
            $gzContent = gzcompress($content, 9);

            if ($this->isRedisEnabled()) {
                $tags = ['weltpixel_ga4', 'weltpixel_ga4_json'];

                $result = $this->cache->save($gzContent, $cacheKey, $tags);
                if (!$result) {
                    $this->saveToFileSystem($fileHash, $gzContent);
                }
            } else {
                $this->saveToFileSystem($fileHash, $gzContent);
            }
        } catch (\Exception $exception) {
           return null;
        }
        return $fileHash;
    }

    /**
     * Save content to filesystem
     * @param string $fileHash
     * @param string $gzContent
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function saveToFileSystem($fileHash, $gzContent)
    {
        $cachePath = $this->fileSystem->getDirectoryWrite($this->directoryList::VAR_DIR);
        $filePath = self::CACHE_PATH . DIRECTORY_SEPARATOR . $fileHash;

        // Ensure directory exists
        $cacheDir = self::CACHE_PATH;
        if (!$cachePath->isDirectory($cacheDir)) {
            $cachePath->create($cacheDir);
        }

        $cachePath->writeFile($filePath, $gzContent);
    }

    /**
     * Get content from cache (Redis or filesystem fallback)
     * @param $fileHash
     * @return string|null
     */
    public function getContentFromFile($fileHash)
    {
        if (empty($fileHash)) {
            return '';
        }

        $cacheKey = 'weltpixel_ga4_' . $fileHash;

        try {
            $gzContent = null;

            if ($this->isRedisEnabled()) {
                $gzContent = $this->cache->load($cacheKey);
                if ($gzContent === false) {
                    $gzContent = $this->getContentFromFileSystem($fileHash);
                }
            } else {
                $gzContent = $this->getContentFromFileSystem($fileHash);
            }

            if ($gzContent === null || $gzContent === false) {
                return '';
            }

            $content = gzuncompress($gzContent);
            if ($content === false) {
                return '';
            }

            return $content;
        } catch (\Exception $ex) {
            return '';
        }
    }

    /**
     * Get content from filesystem
     * @param string $fileHash
     * @return string|null
     */
    private function getContentFromFileSystem($fileHash)
    {
        try {
            $cachePath = $this->fileSystem->getDirectoryRead($this->directoryList::VAR_DIR);
            $filePath = self::CACHE_PATH . DIRECTORY_SEPARATOR . $fileHash;

            if (!$cachePath->isExist($filePath)) {
                return null;
            }

            return $cachePath->readFile($filePath);
        } catch (\Exception $ex) {
            $this->_logger->error('GA4 JsonBuilder getContentFromFileSystem error: ' . $ex->getMessage());
            return null;
        }
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function clearSavedHashes()
    {
        $ga4CachePath = $this->directoryList->getPath($this->directoryList::VAR_DIR) . DIRECTORY_SEPARATOR . self::CACHE_PATH;
        try {
            if ($this->file->isDirectory($ga4CachePath)) {
                $this->file->createDirectory($ga4CachePath . '.archived' );
                $this->file->rename($ga4CachePath, $ga4CachePath . '.archived' . DIRECTORY_SEPARATOR . time());
            }
        } catch (\Exception $ex) {
            return false;
        }
        return true;
    }
}
