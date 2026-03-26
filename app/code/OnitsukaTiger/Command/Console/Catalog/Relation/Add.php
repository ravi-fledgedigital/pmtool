<?php
namespace OnitsukaTiger\Command\Console\Catalog\Relation;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\NoSuchEntityException;
use OnitsukaTiger\Command\Console\Command;
use OnitsukaTiger\Catalog\Model\ConfigurableProduct\Filter;
use OnitsukaTiger\Catalog\Model\ConfigurableProduct\LinkManagement;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Eav\Model\Config;
use OnitsukaTiger\Catalog\Model\Product ;
use Magento\Swatches\Model\Swatch as Swatch;

/**
 * Class Add
 * @package OnitsukaTiger\Command\Console\Catalog\Relation
 */
class Add extends Command
{
    const SKU = 'sku';

    const DEFAULT_STORE_ID = 0;

    /**
     * @var State
     */
    protected $state;

    /**
     * @var Filter
     */
    protected $productFilter;

    /**
     * @var LinkManagement
     */
    protected $linkManagement;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var Config
     */
    protected $eavConfig;

    /**
     * @var \Magento\Swatches\Model\ResourceModel\Swatch\CollectionFactory
     */
    private  $swatchCollectionFactory;

    /**
     * @param \OnitsukaTiger\Logger\Logger $logger
     * @param State $state
     * @param Filter $productFilter
     * @param LinkManagement $linkManagement
     * @param ProductRepositoryInterface $productRepository
     * @param Config $eavConfig
     * @param \Magento\Swatches\Model\ResourceModel\Swatch\CollectionFactory $swatchCollectionFactory
     * @param string|null $name
     */
    public function __construct(
        \OnitsukaTiger\Logger\Logger $logger,
        State $state,
        Filter $productFilter,
        LinkManagement $linkManagement,
        ProductRepositoryInterface $productRepository,
        Config $eavConfig,
        \Magento\Swatches\Model\ResourceModel\Swatch\CollectionFactory $swatchCollectionFactory,
        string $name = null
    ) {
        $this->state = $state;
        $this->productFilter = $productFilter;
        $this->linkManagement = $linkManagement;
        $this->productRepository = $productRepository;
        $this->eavConfig = $eavConfig;
        $this->swatchCollectionFactory = $swatchCollectionFactory;
        parent::__construct($logger, $name);
    }

    /**
     * Configures the current command.
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('catalog:relation:add')
            ->setDescription('Add assignment simple products to a configurable product.')
            ->addOption(
                self::SKU,
                's',
                InputOption::VALUE_OPTIONAL,
                'Sku'
            );

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = microtime(true);
        $this->state->setAreaCode(Area::AREA_CRONTAB);
        $errors = [];

        if ($configurableSku = $input->getOption(self::SKU)) {
            $configurableSku = explode("\n",$configurableSku);
            foreach ($configurableSku as $item) {
                try {
                    $product = $this->productRepository->get($item);
                    $currentVariations = $product->getTypeInstance()->getUsedProductIds($product);
                    $productToAssigns = $this->productFilter->getChildProductsToAssign($item);
                    $productToAssigns = array_keys($productToAssigns->getItems());
                    $skuToAssigns = array_diff($productToAssigns, $currentVariations);
                    if(count($skuToAssigns)) {
                        $childrenIds = $product->getExtensionAttributes()->getConfigurableProductLinks();
                        $optionFactory = $this->linkManagement->getOptionsFactory();
                        $options = $optionFactory->create([]);
                        foreach ($skuToAssigns as $childSku) {
                            $child = $this->productRepository->getById($childSku);
                            $data = $this->linkManagement->addMoreChild($product, $child);
                            $childrenIds[] = $data[1];
                            $options = $data[0];
                        }

                        $product->getExtensionAttributes()->setConfigurableProductOptions($options);
                        $product->getExtensionAttributes()->setConfigurableProductLinks($childrenIds);
                        $this->productRepository->save($product);
                        $output->writeln('<info>Add Associated Simple Products To Configurable Product With SKU: ' . $item . ' Success. </info>');
                    }else {
                        $output->writeln('<info>No more Associated Simple Products added to Configurable Product With SKU: ' . $item . ' Success. </info>');
                    }
                } catch (\Throwable $e){
                    $errors[] = [
                        'sku' => $item,
                        'msg' => $e->getMessage()
                    ];
                }
            }
        }
        if (!empty($errors)) {
            foreach ($errors as $error){
                $output->writeln(sprintf("Error SKU %s : %s ",$error['sku'], $error['msg']));
            }
        }
        $this->addSwatchOptions();
        $time_elapsed_secs = microtime(true) - $start;
        $output->writeln($time_elapsed_secs);

        return Command::SUCCESS;
    }

    /**
     * Save Swatch Options for color code
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function addSwatchOptions(){
        $attribute = $this->eavConfig->getAttribute('catalog_product', Product::COLOR_CODE);
        foreach ($attribute->getSource()->getAllOptions() as $option) {
            if(isset($option['value']) && $option['value']){
                $swatch = $this->loadSwatchIfExists($option['value'], self::DEFAULT_STORE_ID);
                if($swatch) {
                    $swatch->setData('option_id', $option['value']);
                    $swatch->setData('store_id', self::DEFAULT_STORE_ID);
                    $swatch->setData('type', Swatch::SWATCH_TYPE_EMPTY);
                    $swatch->save();
                }
            }
        }
    }

    /**
     * @param $optionId
     * @param $storeId
     * @return false|\Magento\Framework\DataObject
     */
    protected function loadSwatchIfExists($optionId, $storeId)
    {
        $collection = $this->swatchCollectionFactory->create();
        $collection->addFieldToFilter('option_id', $optionId);
        $collection->addFieldToFilter('store_id', $storeId);
        $collection->setPageSize(1);

        $loadedSwatch = $collection->getFirstItem();
        if ($loadedSwatch->getId()) {
            return false;
        }
        return $loadedSwatch;
    }
}
