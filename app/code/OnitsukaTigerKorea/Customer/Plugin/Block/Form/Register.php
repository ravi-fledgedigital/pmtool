<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTigerKorea\Customer\Plugin\Block\Form;

use Magento\Customer\Block\Form\Register as CustomerRegister;
use OnitsukaTigerKorea\Customer\Helper\Data;

/**
 * Class Register
 * @package OnitsukaTigerKorea\Customer\Plugin\Block\Form
 */
class Register
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
     * @param CustomerRegister $subject
     * @param $result
     * @return string
     */
    public function afterGetTemplate(CustomerRegister $subject, $result) {
        if ($this->dataHelper->isCustomerEnabled()) {
            $subject->setTemplate('OnitsukaTigerKorea_Customer::account/form/register.phtml');
        }
        return $result;
    }
}
