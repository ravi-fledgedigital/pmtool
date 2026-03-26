<?php

namespace Gdx\AdminPaymentMethod\Plugin;

/**
 * Class PreSelect
 *
 * @package Gdx\AdminPaymentMethod\Plugin
 */

class PreSelect
{
    /**
     * @var \Gdx\AdminPaymentMethod\Model\AdminPaymentMethod
     */
    private $model;

    /**
     * PreSelect constructor.
     * @param \Gdx\AdminPaymentMethod\Model\AdminPaymentMethod $model
     */
    public function __construct(\Gdx\AdminPaymentMethod\Model\AdminPaymentMethod $model)
    {
        $this->model = $model;
    }

    /**
     * @param \Magento\Sales\Block\Adminhtml\Order\Create\Billing\Method\Form $block
     * @param $result
     * @return bool|string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetSelectedMethodCode(
        \Magento\Sales\Block\Adminhtml\Order\Create\Billing\Method\Form $block,
        $result
    ) {
        if ($result && $result != 'free') {
            return $result;
        }

        $data = $this->model->getDataPreSelect();
        if ($data) {
            $result = \Gdx\AdminPaymentMethod\Model\AdminPaymentMethod::CODE;
            return $result;
        }
        return false;
    }
}
