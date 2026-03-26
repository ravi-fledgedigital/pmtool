<?php

declare(strict_types=1);

namespace OnitsukaTiger\Catalog\Plugin\Page\Config;

use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Config\Metadata\MsApplicationTileImage;

/**
 * Class Helper Data
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Renderer
{
    /**
     * @var Config
     */
    private $pageConfig;
    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;
    /**
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    private $string;


    private $msApplicationTileImage;

    /**
     * Renderer constructor.
     * @param Config $pageConfig
     */
    public function __construct(
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\Escaper $escaper,
        Config $pageConfig,
        MsApplicationTileImage $msApplicationTileImage = null
    ) {
        $this->pageConfig = $pageConfig;
        $this->escaper = $escaper;
        $this->string = $string;
        $this->msApplicationTileImage = $msApplicationTileImage ?:
            \Magento\Framework\App\ObjectManager::getInstance()->get(MsApplicationTileImage::class);
    }

    /**
     * @param Config\Renderer $subject
     * @param $result
     * @return string
     */
    public function afterRenderTitle(\Magento\Framework\View\Page\Config\Renderer $subject, $result)
    {
        return '<title>' . $this->pageConfig->getTitle()->get() . '</title>' . "\n";
    }


    /**
     * Render metadata
     *
     * @return string
     */
    public function aroundRenderMetadata(\Magento\Framework\View\Page\Config\Renderer $subject, callable $proceed)
    {
        $result = '';
        foreach ($this->pageConfig->getMetadata() as $name => $content) {
            $metadataTemplate = $this->getMetadataTemplate($name);
            if (!$metadataTemplate) {
                continue;
            }
            $content = $this->processMetadataContent($name, $content);
            if ($content) {
                $result .= str_replace(['%name', '%content'], [$name, $content], $metadataTemplate);
            }
        }
        return $result;
    }

    /**
     * Returns metadata template
     *
     * @param string $name
     * @return bool|string
     */
    protected function getMetadataTemplate($name)
    {
        if (strpos($name, 'og:') === 0) {
            return '<meta property="' . $name . '" content="%content"/>' . "\n";
        }

        switch ($name) {
            case Config::META_CHARSET:
                $metadataTemplate = '<meta charset="%content"/>' . "\n";
                break;

            case Config::META_CONTENT_TYPE:
                $metadataTemplate = '<meta http-equiv="Content-Type" content="%content"/>' . "\n";
                break;

            case Config::META_X_UI_COMPATIBLE:
                $metadataTemplate = '<meta http-equiv="X-UA-Compatible" content="%content"/>' . "\n";
                break;

            case Config::META_MEDIA_TYPE:
                $metadataTemplate = false;
                break;

            default:
                $metadataTemplate = '<meta name="%name" content="%content"/>' . "\n";
                break;
        }
        return $metadataTemplate;
    }

    /**
     * Process metadata content
     *
     * @param string $name
     * @param string $content
     * @return mixed
     */
    protected function processMetadataContent($name, $content)
    {
        $method = 'get' . $this->string->upperCaseWords($name, '_', '');
        if ($name === 'title') {
            if (!$content) {
                $content = $this->pageConfig->$method()->get();
            }
            return $content;
        }
        if (method_exists($this->pageConfig, $method)) {
            $content = $this->pageConfig->$method();
        }
        if ($content && $name === $this->msApplicationTileImage::META_NAME) {
            $content = $this->msApplicationTileImage->getUrl($content);
        }

        return $content;
    }
}
