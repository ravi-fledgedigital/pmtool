<?php
namespace OnitsukaTiger\Fixture\Model\Cms;

/**
 * Class Block
 * @package OnitsukaTiger\Fixture\Model\Cms
 */
class Block
{
    /**
     * @var \OnitsukaTiger\Fixture\Model\SampleData\FixtureManager
     */
    private $fixtureManager;

    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $csvReader;

    /**
     * @var \Magento\Cms\Model\BlockFactory
     */
    protected $blockFactory;

    /**
     * @var Block\Converter
     */
    protected $converter;



    /**
     * Block constructor.
     * @param \OnitsukaTiger\Fixture\Model\SampleData\FixtureManager $fixtureManager
     * @param \Magento\Framework\File\Csv $csvReader
     * @param \Magento\Cms\Model\BlockFactory $blockFactory
     * @param Block\Converter $converter
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        \OnitsukaTiger\Fixture\Model\SampleData\FixtureManager $fixtureManager,
        \Magento\Framework\File\Csv $csvReader,
        \Magento\Cms\Model\BlockFactory $blockFactory,
        \OnitsukaTiger\Fixture\Model\Cms\Block\Converter $converter
    )
    {
        $this->fixtureManager = $fixtureManager;
        $this->csvReader = $csvReader;
        $this->blockFactory = $blockFactory;
        $this->converter = $converter;
    }

    public function install(array $fixtures)
    {
        foreach ($fixtures as $fileName) {
            $fileName = $this->fixtureManager->getFixture($fileName);
            if (!file_exists($fileName)) {
                continue;
            }

            $rows = $this->csvReader->getData($fileName);
            $header = array_shift($rows);

            foreach ($rows as $row) {
                $data = [];
                foreach ($row as $key => $value) {
                    if (array_key_exists($key, $header)) {
                        $data[$header[$key]] = $value;
                    }
                }
                $row = $data;
                $data = $this->converter->convertRow($row);
                $cmsBlock = $this->saveCmsBlock($data['block']);
                $cmsBlock->unsetData();
            }
        }
    }

    /**
     * @param array $data
     * @return \Magento\Cms\Model\Block
     */
    protected function saveCmsBlock($data)
    {
        $cmsBlock = $this->blockFactory->create();

        if (isset($data['store_code'])) {
            $storeId = $this->converter->getStoresIds($data)[0];
            $cmsBlock->setStoreId($storeId)->load($data['identifier']);
        }else{
            $cmsBlock->getResource()->load($cmsBlock, $data['identifier']);
        }

        if (!$cmsBlock->getData()) {
            $cmsBlock->setData($data);
        } else {
            $cmsBlock->addData($data);
        }
        if (isset($data['store_code'])) {
            $cmsBlock->setStores($this->converter->getStoresIds($data));
        }
        $cmsBlock->setIsActive(1);
        $cmsBlock->save();
        return $cmsBlock;
    }
}
