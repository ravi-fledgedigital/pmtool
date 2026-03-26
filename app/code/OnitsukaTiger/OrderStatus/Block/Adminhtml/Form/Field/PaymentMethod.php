<?php
declare(strict_types=1);

namespace OnitsukaTiger\OrderStatus\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Magento\Payment\Model\Config\Source\Allmethods;

class PaymentMethod extends Select
{
    /**
     * @var Allmethods
     */
    private $paymentMethods;

    /**
     * OrderStatus constructor.
     * @param Context $context
     * @param Allmethods $paymentMethods
     * @param array $data
     */
    public function __construct(
        Context $context,
        Allmethods $paymentMethods,
        array $data = []
    ) {
        $this->paymentMethods = $paymentMethods;
        parent::__construct($context, $data);
    }

    /**
     * Set "name" for <select> element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Set "id" for <select> element
     *
     * @param $value
     * @return $this
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }
        return parent::_toHtml();
    }

    /**
     * @return array
     */
    private function getSourceOptions(): array
    {
        $options = $this->paymentMethods->toOptionArray();
        array_unshift($options, ['value' => '', 'label' => __('-- Please Select --')]);
        return $options;
    }
}
