<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTigerKorea\Customer\Ui\Component\Form\Field;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponentInterface;
use OnitsukaTigerKorea\Customer\Helper\Data;

/**
 * Class LastName
 * @package OnitsukaTigerKorea\Customer\Ui\Component\Form\Field
 */
class LastName extends \Magento\Ui\Component\Form\Field
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
     * LastName constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Data $dataHelper
     * @param CustomerRepositoryInterface $customerRepository
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Data $dataHelper,
        CustomerRepositoryInterface $customerRepository,
        array $components = [],
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        $this->customerRepository = $customerRepository;
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
        if (isset($params['id'])) {
            $customer = $this->customerRepository->getById($params['id']);
            if ($this->dataHelper->isCustomerEnabled($customer->getStoreId())) {
                $currentConfig = $this->getData('config');
                $currentConfig['validation']['required-entry'] = false;
                $currentConfig['visible'] = false;

                $this->setData('config', $currentConfig);
            }
        }

    }
}
