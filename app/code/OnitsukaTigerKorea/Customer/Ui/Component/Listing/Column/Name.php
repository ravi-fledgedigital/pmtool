<?php
/**
 * Copy my Magento
 */
namespace OnitsukaTigerKorea\Customer\Ui\Component\Listing\Column;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use OnitsukaTigerKorea\Customer\Helper\Data;

/**
 * Class Name
 * @package OnitsukaTigerKorea\Customer\Ui\Component\Listing\Column
 */
class Name extends Column
{
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var Data
     */
    private $dataHelper;

    /**
     * @var \OnitsukaTigerKorea\MaskCustomerData\Helper\Data
     */
    private \OnitsukaTigerKorea\MaskCustomerData\Helper\Data $helper;

    /**
     * Name constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param Data $dataHelper
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        Data $dataHelper,
        \OnitsukaTigerKorea\MaskCustomerData\Helper\Data $helper,
        array $components = [],
        array $data = []
    ) {
        $this->customerRepository = $customerRepository;
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->helper = $helper;
    }

    /**
     * @param array $dataSource
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $customer = $this->customerRepository->getById($item['entity_id']);
                if ($this->dataHelper->isCustomerEnabled($customer->getStoreId())) {
                    $item['name'] = $customer->getFirstname();
                }
                if ($item['website_id'][0] == 4) {
                    if (isset($item['name'])) {
                        $item['name'] = $this->helper->maskName($item['name']);
                    }
                }
            }
        }
        return $dataSource;
    }
}
