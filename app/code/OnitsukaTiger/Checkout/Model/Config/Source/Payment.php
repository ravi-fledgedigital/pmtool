<?php

namespace OnitsukaTiger\Checkout\Model\Config\Source;

class Payment implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options array
     *
     * @var array
     */
    protected $_options;


    /**
     * @var \Magento\Payment\Model\Config\Source\Allmethods
     */
    private $allPaymentMethod;

    /**
     * Payment constructor.
     * @param \Magento\Payment\Model\Config\Source\Allmethods $allPaymentMethod
     */
    public function __construct(
        \Magento\Payment\Model\Config\Source\Allmethods $allPaymentMethod
    )
    {
        $this->allPaymentMethod = $allPaymentMethod;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->_options) {
            $paymentMethods = $this->getAllPaymentMethods();
            $methodsOptions = $offlineMethod = null ;
            foreach ($paymentMethods as $method) {
                if (is_array($method['value'])) {
                    $offlineMethod = $method;
                }else{
                    $label = '( '.ucfirst(explode('_', $method['value'])[0]) .' ) '. $method['label'];
                    $methodsOptions[] = ['value' => $method['value'], 'label' => $label];
                }
            }
            $methodsOptionsGroup[] = [
                'label' => 'Online Payment Methods',
                'value' => $methodsOptions
            ];
            if($offlineMethod) {
                $methodsOptionsGroup[] = $offlineMethod;
            }
            $this->_options = $methodsOptionsGroup;
        }
        return $this->_options;;
    }


    /**
     * All Payment Method in Magento 2 backend
     *
     * @return Array
     */
    public function getAllPaymentMethods()
    {
        return $this->allPaymentMethod->toOptionArray();
    }
}
