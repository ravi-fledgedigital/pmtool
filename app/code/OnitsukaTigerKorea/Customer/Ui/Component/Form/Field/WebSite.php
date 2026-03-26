<?php
declare(strict_types=1);

namespace OnitsukaTigerKorea\Customer\Ui\Component\Form\Field;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\StoreRepository;
use OnitsukaTigerKorea\Customer\Helper\Data;

/**
 *
 * @package OnitsukaTigerKorea\Customer\Ui\Component\Form\Field
 */
class WebSite extends \Magento\Ui\Component\Form\Field
{
    /**
     * Address Helper
     *
     * @var Data
     */
    private $dataHelper;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var StoreRepository
     */
    private $storeRepository;

    /**
     * LastName constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Data $dataHelper
     * @param CustomerRepositoryInterface $customerRepository
     * @param StoreRepository $StoreRepository
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Data $dataHelper,
        CustomerRepositoryInterface $customerRepository,
        StoreRepository $StoreRepository,
        array $components = [],
        array $data = []
    )
    {
        $this->dataHelper = $dataHelper;
        $this->customerRepository = $customerRepository;
        $this->storeRepository = $StoreRepository;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare component configuration
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepare()
    {
        parent::prepare();
        $rules = [];
        $currentConfig = $this->getData('config');

        $stores = $this->storeRepository->getList();
        foreach ($stores as $store) {
            $rules[] = [
                'value' => $store["website_id"],
                'actions' => [
                    [
                        'target' => 'customer_form.areas.customer.customer.lastname',
                        'callback' => $this->dataHelper->isCustomerEnabled($store['store_id']) ? 'hide' : 'show'
                    ]
                ]
            ];
        }

        $currentConfig['switcherConfig'] = [
            'enabled' => true,
            'rules' => $rules
        ];

        $this->setData('config', $currentConfig);
    }

}
