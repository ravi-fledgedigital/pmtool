<?php

namespace OnitsukaTiger\NetSuiteStoreShipping\Plugin\BackendMenu;

use Magento\Backend\Model\Menu;
use Magento\Backend\Model\Menu\Item;
use Magento\Backend\Model\Menu\ItemFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use OnitsukaTiger\NetSuiteStoreShipping\Helper\Data;
use OnitsukaTiger\NetSuiteStoreShipping\Model\GetInformationSource;

class Builder
{
    const MODULE_NAME_SHIPPING_FROM_STORE = 'OnitsukaTiger_NetSuiteStoreShipping';
    const PATH_REDIRECT = 'store_shipping/shipment/index/source_code/';
    const INDEX_DEFAULT_VALUE = 1;
    private ItemFactory $itemFactory;
    private GetInformationSource $getInformationSource;
    private Data $helper;

    /**
     * @param ItemFactory $itemFactory
     * @param GetInformationSource $getInformationSource
     * @param Data $helper
     */
    public function __construct(
        ItemFactory $itemFactory,
        GetInformationSource $getInformationSource,
        Data $helper
    ) {
        $this->itemFactory = $itemFactory;
        $this->getInformationSource = $getInformationSource;
        $this->helper = $helper;
    }

    /**
     * @param $subject
     * @param Menu $menu
     * @return Menu
     * @throws NoSuchEntityException
     */
    public function afterGetResult($subject, Menu $menu): Menu
    {
        return $this->buildMenu($menu);
    }

    /**
     * @param Menu $menu
     * @return Menu
     * @throws NoSuchEntityException
     */
    private function buildMenu(Menu $menu): Menu
    {
        $shippingFromStoreData = $this->getInformationSource->getShippingFromStoreData('menu');
        $dataDuplicate = $this->getInformationSource->getSourceDuplicate('menu');
        $data = [];
        foreach ($dataDuplicate as $item) {
            $data[$item->getName()] = $item->getSourceCode();
        }

        foreach ($shippingFromStoreData as $websiteCode => $websiteData) {
            $mainItem = $this->itemFactory->create(['data' => [
              'id' => self::MODULE_NAME_SHIPPING_FROM_STORE . '::' . $this->replaceString($websiteCode),
              'title' => $this->helper->getWebsiteName($websiteCode),
              'module' => self::MODULE_NAME_SHIPPING_FROM_STORE,
              'resource' => self::MODULE_NAME_SHIPPING_FROM_STORE . '::' . $this->replaceString($websiteCode),
          ]]);
            $menu->add($mainItem, self::MODULE_NAME_SHIPPING_FROM_STORE . '::manage', self::INDEX_DEFAULT_VALUE);

            foreach ($websiteData as $keySource => $source) {
                if (in_array($keySource, $data)) {
                    continue;
                }
                $renderedItems = $this->renderItem(
                    self::MODULE_NAME_SHIPPING_FROM_STORE . '::' . $keySource,
                    __($source)->render(),
                    self::MODULE_NAME_SHIPPING_FROM_STORE . '::' . $keySource,
                    $keySource
                );
                $menu->add($renderedItems, self::MODULE_NAME_SHIPPING_FROM_STORE . '::' . $this->replaceString($websiteCode));
            }
        }

        if (count($data) > 0) {
            $mainItem = $this->itemFactory->create(['data' => [
                'id' => self::MODULE_NAME_SHIPPING_FROM_STORE . '::' . 'global',
                'title' => 'Global',
                'module' => self::MODULE_NAME_SHIPPING_FROM_STORE,
                'resource' => self::MODULE_NAME_SHIPPING_FROM_STORE . '::' . 'global'
            ]]);
            $menu->add($mainItem, self::MODULE_NAME_SHIPPING_FROM_STORE . '::manage');

            foreach ($data as $key => $sourceGlobal) {
                $mainItem = $this->itemFactory->create(['data' => [
                    'id' => self::MODULE_NAME_SHIPPING_FROM_STORE . '::' . $sourceGlobal,
                    'title' => $key,
                    'module' => self::MODULE_NAME_SHIPPING_FROM_STORE,
                    'resource' => self::MODULE_NAME_SHIPPING_FROM_STORE . '::' . $sourceGlobal,
                    'action' => self::PATH_REDIRECT . $sourceGlobal,
                ]]);
                $menu->add($mainItem, self::MODULE_NAME_SHIPPING_FROM_STORE . '::' . 'global');
            }
        }

        return $menu;
    }

    /**
     * @param $id
     * @param $title
     * @param $resource
     * @param $url
     * @return Item|null
     */
    private function renderItem($id, $title, $resource, $url): ?Item
    {
        return $this->itemFactory->create(['data' => [
            'id' => $id,
            'module' => self::MODULE_NAME_SHIPPING_FROM_STORE,
            'title' => $title,
            'action' => self::PATH_REDIRECT . $url,
            'resource' => $resource
        ]]);
    }

    /**
     * @param $key
     * @return array|mixed|string|string[]|null
     * @throws NoSuchEntityException
     */
    public function replaceString($key): mixed
    {
        return str_replace(' ', '_', $this->helper->getWebsiteName($key));
    }
}
