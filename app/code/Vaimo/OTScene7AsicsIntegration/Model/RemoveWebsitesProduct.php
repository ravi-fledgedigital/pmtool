<?php

namespace Vaimo\OTScene7AsicsIntegration\Model;

use Magento\Catalog\Model\Product\Action;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollection;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveWebsitesProduct
{
    private ProductCollection $collection;
    private Action $action;
    private StoreManagerInterface $storeManager;

    /**
     * @param ProductCollection $collection
     * @param Action $action
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ProductCollection     $collection,
        Action                $action,
        StoreManagerInterface $storeManager
    ) {
        $this->collection = $collection;
        $this->action = $action;
        $this->storeManager = $storeManager;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->listSkuDeleted($input, $output);
        $this->getProductSkusWithoutImage($output);
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getProductCollection()
    {
        $collectionFactory = $this->collection->create();
        $collectionFactory
            ->addAttributeToSelect("scene7_available_image_angles")
            ->addFieldToFilter("type_id", "configurable")
            ->addAttributeToFilter([['attribute' => 'scene7_available_image_angles', 'null' => true]]);
        
        return $collectionFactory;
    }

    /**
     * @param $output
     * @return void
     */
    public function getProductSkusWithoutImage($output)
    {
        $output->writeln('=====================> Lists all SKUs without images: <=========================');
        $listProductSku = $this->getProductCollection();
        $listSkus = [];
        if ($listProductSku->getSize() > 0) {
            foreach ($listProductSku as $item) {
                $listSkus[] = $item->getSku();
            }
            $output->writeln(implode(PHP_EOL, $listSkus));
        } else {
            $output->writeln("No SKU has been updated in the current run");
        }
    }

    /**
     * @param $output
     * @return void
     */
    public function listSkuDeleted($input, $output)
    {
        $websiteIds = $this->storeManager->getWebsites();
        $showLog =  $input->getOption('log') ?? '';
        $ids = [];
        foreach ($websiteIds as $website) {
            $ids[] = $website->getId();
        }
        $listProductSku = $this->getProductCollection()->addWebsiteFilter($ids);
        $output->writeln('=====================> Lists SKUs has just been removed from all websites with the current Jenkin job: <=========================');
        if ($listProductSku->getSize() > 0) {
            foreach ($listProductSku as $item) {
                if ($showLog == '') {
                    $this->action->updateWebsites(
                        [$item->getId()],
                        $item->getWebsiteIds(),
                        'remove'
                    );
                }
                $output->writeln($item->getSku());
            }
        } else {
            $output->writeln("No SKU has been updated");
        }
    }
}
