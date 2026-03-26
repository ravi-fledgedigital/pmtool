<?php
namespace OnitsukaTiger\Fixture\Model\Cms;

/**
 * Class Page
 * @package OnitsukaTiger\Fixture\Model\Cms
 */
class Page
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
     * @var \Magento\Cms\Model\PageFactory
     */
    protected $pageFactory;

    /**
     * @var Page\Converter
     */
    protected $converter;

    /**
     * Page constructor.
     * @param \OnitsukaTiger\Fixture\Model\SampleData\FixtureManager $fixtureManager
     * @param \Magento\Framework\File\Csv $csvReader
     * @param Page\Converter $converter
     * @param \Magento\Cms\Model\PageFactory $pageFactory
     */
    public function __construct(
        \OnitsukaTiger\Fixture\Model\SampleData\FixtureManager $fixtureManager,
        \Magento\Framework\File\Csv $csvReader,
        \OnitsukaTiger\Fixture\Model\Cms\Page\Converter $converter,
        \Magento\Cms\Model\PageFactory $pageFactory
    ) {
        $this->fixtureManager = $fixtureManager;
        $this->csvReader = $csvReader;
        $this->pageFactory = $pageFactory;
        $this->converter = $converter;
    }

    /**
     * @param array $fixtures
     * @throws \Exception
     */
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

                $this->pageFactory->create()
                    ->load($row['identifier'], 'identifier')
                    ->addData($row)
                    ->setStores($this->converter->getStoresIds($row))
                    ->save();
            }
        }
    }
}
