<?php
namespace OnitsukaTiger\Command\Console\Tools;

/**
 * Class NetSuiteEnableImport
 */
class NetSuiteEnableImport extends \OnitsukaTiger\Command\Console\Command
{
    const OPTION_SKU = 'sku';

    /**
     * @var \Magento\Framework\App\State
     */
    protected $state;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var \Magento\Catalog\Model\Product\Attribute\Repository
     */
    protected $productAttributeRepository;
    /**
     * @var \OnitsukaTiger\NetSuite\Model\SuiteTalk\SearchProduct
     */
    protected $searchProduct;
    /**
     * @var array
     */
    protected $attributes;

    /**
     * @param \OnitsukaTiger\Logger\Logger $logger
     * @param \Magento\Framework\App\State $state
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Catalog\Model\Product\Attribute\Repository $productAttributeRepository
     * @param \OnitsukaTiger\NetSuite\Model\SuiteTalk\SearchProduct $searchProduct
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        \OnitsukaTiger\Logger\Logger $logger,
        \Magento\Framework\App\State $state,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\Product\Attribute\Repository $productAttributeRepository,
        \OnitsukaTiger\NetSuite\Model\SuiteTalk\SearchProduct $searchProduct
    ) {
        $this->state = $state;
        $this->productRepository = $productRepository;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->searchProduct = $searchProduct;

        parent::__construct($logger);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $options = [
            new \Symfony\Component\Console\Input\InputOption(
                self::OPTION_SKU,
                's',
                \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED | \Symfony\Component\Console\Input\InputOption::VALUE_IS_ARRAY,
                'SKU'
            )
        ];

        $this->setName('tools:netsuite:enable:import');
        $this->setDescription('import enable disable product from NetSuite');
        $this->setDefinition($options);
        parent::configure();
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return null|int
     */
    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
    ) {
        $attributes = [];
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        $codes = [
            \OnitsukaTiger\NetSuite\Model\SuiteTalk\EnableProduct::WEBSITE_ID_THAILAND =>
                \OnitsukaTiger\NetSuite\Model\SuiteTalk\EnableProduct::NETSUITE_ID_THAILAND,
            \OnitsukaTiger\NetSuite\Model\SuiteTalk\EnableProduct::WEBSITE_ID_MALAYSIA =>
                \OnitsukaTiger\NetSuite\Model\SuiteTalk\EnableProduct::NETSUITE_ID_MALAYSIA,
            \OnitsukaTiger\NetSuite\Model\SuiteTalk\EnableProduct::WEBSITE_ID_SINGAPORE =>
                \OnitsukaTiger\NetSuite\Model\SuiteTalk\EnableProduct::NETSUITE_ID_SINGAPORE,
            \OnitsukaTiger\NetSuite\Model\SuiteTalk\EnableProduct::WEBSITE_ID_VIETNAM =>
                \OnitsukaTiger\NetSuite\Model\SuiteTalk\EnableProduct::NETSUITE_ID_VIETNAM

        ];
        /** @var \Magento\Eav\Api\Data\AttributeInterface $attribute */
        $attribute = $this->productAttributeRepository->get('netsuite_enable');
        $options = $attribute->getOptions();
        foreach($options as $option) {
            $label = $option->getLabel();
            if(array_key_exists($label, $codes)) {
                $code = $codes[$label];
                $attributes[$code] = $option->getValue();
            }
        }
        $this->attributes = $attributes;

        $arr = $input->getOption(self::OPTION_SKU);
        foreach($arr as $sku) {
            $this->updateBySku(
                $input,
                $output,
                $sku
            );
        }
    }

    private function updateBySku(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output,
        $sku
    )
    {
        $output->writeln(sprintf('%s',$sku));
        $values = $this->searchProduct->searchEnableFlagBySku($sku);
        $ret = [];
        foreach($values as $val) {
            /** @var \NetSuite\Classes\ListOrRecordRef $val */
            $ret[] = $this->attributes[$val->internalId];
            $output->writeln(json_encode($val));
        }
        $product = $this->productRepository->get($sku);
        $product->setData('netsuite_enable', implode(',', $ret));
        $this->productRepository->save($product);
    }
}
