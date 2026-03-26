<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Model\Tabs;

use Magento\Framework\App\Cache\Type\Layout as LayoutCache;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class Loader
{
    public const TABS_LOADED_FLAG = 'TABS_LOADED_STORE_';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var LayoutCache
     */
    private $layoutCache;

    /**
     * @var Loader\ReadHandler
     */
    private $readHandler;

    /**
     * @var Loader\SaveHandler
     */
    private $saveHandler;

    public function __construct(
        StoreManagerInterface $storeManager,
        LayoutCache $layoutCache,
        Loader\ReadHandler $readHandler,
        Loader\SaveHandler $saveHandler
    ) {
        $this->storeManager = $storeManager;
        $this->layoutCache = $layoutCache;
        $this->readHandler = $readHandler;
        $this->saveHandler = $saveHandler;
    }

    public function execute()
    {
        $allStores = $this->storeManager->getStores();
        $allStoreIds = $this->getAllStoreIds($allStores);
        foreach ($allStores as $store) {
            $storeId = $store->getId();
            if ($this->layoutCache->load(self::TABS_LOADED_FLAG . $storeId)) {
                continue;
            }

            $layout = $this->readHandler->execute($storeId);
            $this->saveHandler->execute($layout, $storeId, $allStoreIds);
            $this->layoutCache->save('loaded', self::TABS_LOADED_FLAG . $storeId);
        }
    }

    /**
     * @param StoreInterface[] $allStores
     * @return array
     */
    private function getAllStoreIds(array $allStores): array
    {
        $storeIds = [];
        foreach ($allStores as $store) {
            $storeIds[] = $store->getId();
        }

        return $storeIds;
    }
}
