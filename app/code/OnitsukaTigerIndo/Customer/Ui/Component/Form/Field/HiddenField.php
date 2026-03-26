<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/** phpcs:ignoreFile */
declare(strict_types=1);

namespace OnitsukaTigerIndo\Customer\Ui\Component\Form\Field;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use OnitsukaTigerIndo\Customer\Helper\Data;

/**
 * Class HiddenField
 * @package OnitsukaTigerIndo\Customer\Ui\Component\Form\Field
 */
class HiddenField extends \Magento\Ui\Component\Form\Field
{
    /**
     * @var AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * HiddenField constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param AddressRepositoryInterface $addressRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param Data $helperData
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        AddressRepositoryInterface $addressRepository,
        CustomerRepositoryInterface $customerRepository,
        Data $helperData,
        array $components = [],
        array $data = []
    ) {
        $this->addressRepository = $addressRepository;
        $this->customerRepository = $customerRepository;
        $this->helperData = $helperData;
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

        $params = $this->context->getRequestParams();
        if (isset($params['entity_id']) && $params['entity_id'] != '') {
            $address = $this->addressRepository->getById($params['entity_id']);
            $customerId = $address->getCustomerId();
        } else {
            $customerId = $params['parent_id'];
        }

        $customer = $this->customerRepository->getById($customerId);
        if (!$this->helperData->isEnableModule($customer->getStoreId())) {
            $this->hiddenField();
        }
    }

    /**
     *
     */
    public function hiddenField() {
        $currentConfig['visible'] = false;
        $this->setData('config', $currentConfig);
    }
}
