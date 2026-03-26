<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Model\Tabs\Loader;

use Amasty\CustomTabs\Model\Layout\GeneratorPool;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\Layout\ReaderPool;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Page\BuilderFactory;
use Magento\Store\Model\App\Emulation;

class ReadHandler
{
    /**
     * @var State
     */
    private $appState;

    /**
     * @var Emulation
     */
    private $appEmulation;

    /**
     * @var LayoutFactory
     */
    private $layoutFactory;

    /**
     * @var ReaderPool
     */
    private $layoutReaderPool;

    /**
     * @var GeneratorPool
     */
    private $generatorPool;

    /**
     * @var BuilderFactory
     */
    private $pageBuilderFactory;

    public function __construct(
        State $appState,
        Emulation $appEmulation,
        LayoutFactory $layoutFactory,
        ReaderPool $layoutReaderPool,
        GeneratorPool $generatorPool,
        BuilderFactory $pageBuilderFactory
    ) {
        $this->appState = $appState;
        $this->appEmulation = $appEmulation;
        $this->layoutFactory = $layoutFactory;
        $this->layoutReaderPool = $layoutReaderPool;
        $this->generatorPool = $generatorPool;
        $this->pageBuilderFactory = $pageBuilderFactory;
    }

    /**
     * @param $storeId
     *
     * @return LayoutInterface
     */
    public function execute($storeId)
    {
        $layout = $this->appState->emulateAreaCode(
            Area::AREA_FRONTEND,
            [$this, 'loadLayout'],
            [$storeId]
        );
        $this->appEmulation->stopEnvironmentEmulation();

        return $layout;
    }

    /**
     * @param $storeId
     *
     * @return LayoutInterface
     */
    public function loadLayout($storeId)
    {
        $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
        $layout = $this->layoutFactory->create([
            'reader' => $this->layoutReaderPool,
            'generatorPool' => $this->generatorPool
        ]);
        $layout->setBuilder($this->pageBuilderFactory->create(['layout' => $layout]));
        $layout->getUpdate()->addHandle([
            'default',
            'catalog_product_view'
        ]);
        $layout->publicBuild();

        return $layout;
    }
}
