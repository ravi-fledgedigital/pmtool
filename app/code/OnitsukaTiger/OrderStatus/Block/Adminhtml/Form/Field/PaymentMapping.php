<?php
declare(strict_types=1);

namespace OnitsukaTiger\OrderStatus\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\BlockInterface;

class PaymentMapping extends AbstractFieldArray
{
    /**
     * @var BlockInterface|OrderStatus
     */
    private $statusesRenderer;

    /**
     * @var BlockInterface|PaymentMethod
     */
    private $paymentMethodRenderer;

    /**
     * Prepare rendering the new field by adding all the needed columns
     * @throws LocalizedException
     */
    protected function _prepareToRender()
    {
        $this->addColumn('method', [
            'label' => __('Payment Method'),
            'renderer' => $this->getPaymentMethodsRenderer(),
            'class' => 'required-entry'
        ]);

        $this->addColumn('statuses', [
            'label' => __('Order Status'),
            'renderer' => $this->getStatusesRenderer(),
            'class' => 'required-entry'
        ]);
        $this->_addAfter = false;
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row)
    {
        $options = [];

        $method = $row->getMethod();
        if (!empty($method)) {
            $options['option_' . $this->getPaymentMethodsRenderer()->calcOptionHash($method)] = 'selected="selected"';
        }

        $statuses = $row->getStatuses();
        if (!empty($statuses)) {
            foreach ($statuses as $status) {
                $options['option_' . $this->getStatusesRenderer()->calcOptionHash($status)] = 'selected="selected"';
            }
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * @return BlockInterface|PaymentMethod
     * @throws LocalizedException
     */
    private function getPaymentMethodsRenderer()
    {
        if (!$this->paymentMethodRenderer) {
            $this->paymentMethodRenderer = $this->getLayout()->createBlock(
                PaymentMethod::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->paymentMethodRenderer;
    }

    /**
     * @return BlockInterface|OrderStatus
     * @throws LocalizedException
     */
    private function getStatusesRenderer()
    {
        if (!$this->statusesRenderer) {
            $this->statusesRenderer = $this->getLayout()->createBlock(
                OrderStatus::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->statusesRenderer;
    }
}
