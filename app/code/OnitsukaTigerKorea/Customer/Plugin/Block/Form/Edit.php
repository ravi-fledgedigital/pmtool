<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTigerKorea\Customer\Plugin\Block\Form;

use Magento\Customer\Block\Form\Edit as CustomerEdit;
use OnitsukaTigerKorea\Customer\Helper\Data;

/**
 * Class Edit
 * @package OnitsukaTigerKorea\Customer\Plugin\Block\Form
 */
class Edit
{
    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * LayoutProcessor constructor.
     * @param Data $dataHelper
     */
    public function __construct(
        Data $dataHelper
    )
    {
        $this->dataHelper = $dataHelper;
    }

    /**
     * @param CustomerEdit $subject
     * @param $result
     * @return string
     */
    public function afterGetTemplate(CustomerEdit $subject, $result) {
        if ($this->dataHelper->isCustomerEnabled()) {
            $subject->setTemplate('OnitsukaTigerKorea_Customer::account/form/edit.phtml');
        }
        return $result;
    }
}
