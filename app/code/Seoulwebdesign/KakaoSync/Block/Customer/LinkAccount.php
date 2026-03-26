<?php
/**
 * Copyright © a All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Seoulwebdesign\KakaoSync\Block\Customer;

use Magento\Framework\View\Element\Template\Context;

class LinkAccount extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Customer\Block\Form\Register
     */
    protected $registerFormBlock;

    /**
     * Constructor
     *
     * @param Context $context
     * @param \Magento\Customer\Block\Form\Register $registerFormBlock
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Magento\Customer\Block\Form\Register $registerFormBlock,
        array $data = []
    ) {
        $this->registerFormBlock = $registerFormBlock;
        parent::__construct($context, $data);
    }

    /**
     * Get Register From Block
     *
     * @return \Magento\Customer\Block\Form\Register
     */
    public function getRegisterFromBlock()
    {
        return $this->registerFormBlock;
    }

    /**
     * Get form data
     *
     * @return array|\Magento\Framework\DataObject|mixed|null
     */
    public function getFormData()
    {
        $data = $this->getData('form_data');
        if ($data === null) {
            $formData = [];
            $formData = $this->getRequest()->getParams();
            $data = new \Magento\Framework\DataObject();
            if ($formData) {
                $data->addData($formData);
                $data->setCustomerData(1);
            }
            if (isset($data['region_id'])) {
                $data['region_id'] = (int)$data['region_id'];
            }
            $this->setData('form_data', $data);
        }
        return $data;
    }
}
