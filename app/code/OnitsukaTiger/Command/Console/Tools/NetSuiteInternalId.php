<?php
namespace OnitsukaTiger\Command\Console\Tools;

use Magento\Checkout\Exception;
use Magento\Framework\Exception\InputException;
use Symfony\Component\Console\Command\Command;

/**
 * Class NetSuiteInternalId
 */
class NetSuiteInternalId extends \OnitsukaTiger\Command\Console\Command
{
    const OPTION_SKU = 'sku';

    /**
     * @var \Magento\Framework\App\State
     */
    protected $state;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;
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
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \OnitsukaTiger\NetSuite\Model\SuiteTalk\SearchProduct $searchProduct
     */
    public function __construct(
        \OnitsukaTiger\Logger\Logger $logger,
        \Magento\Framework\App\State $state,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \OnitsukaTiger\NetSuite\Model\SuiteTalk\SearchProduct $searchProduct
    ) {
        $this->state = $state;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
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

        $this->setName('tools:netsuite:internalid:import');
        $this->setDescription('import product internal id from NetSuite');
        $this->setDefinition($options);
        parent::configure();
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return null|int
     * @throws Exception
     */
    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
    ) {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        $this->storeManager->setCurrentStore('admin');
        $errors = [];
        $arr = $input->getOption(self::OPTION_SKU);
        foreach($arr as $sku) {
            try {
                $this->updateBySku(
                    $input,
                    $output,
                    $sku
                );
            } catch (\Throwable $e) {
                $errors[] = [
                    'sku' => $sku,
                    'msg' => $e->getMessage()
                ];
            }
        }
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $output->writeln(sprintf('skip %s : %s', $error['sku'], $error['msg']));
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param $sku
     * @throws InputException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    private function updateBySku(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output,
        $sku
    )
    {
        $item = $this->searchProduct->searchBySku($sku);
        $internalId = $item->internalId;
        $product = $this->productRepository->get($sku);
        $product->setStoreId(0);
        $current = $product->getData('netsuite_internal_id');
        if($current == $item->internalId) {
            $output->writeln(sprintf('SKU %s has same internal id with NetSuite %s', $sku, $internalId));
        } else {
            $output->writeln(sprintf('SKU %s internal Update to %s', $sku, $internalId));
            $product->setData('netsuite_internal_id', $internalId);
        }
        $this->productRepository->save($product);
    }
}
