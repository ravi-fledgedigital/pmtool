<?php
declare(strict_types=1);

namespace OnitsukaTiger\OrderStatus\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Html\Select;
use Magento\Framework\View\Element\Context;
use OnitsukaTiger\OrderStatus\Block\Adminhtml\Form\Field\Source\OrderStatus as Status;

class OrderStatus extends Select
{
    /**
     * @var Status
     */
    private $orderStatuses;

    /**
     * OrderStatus constructor.
     * @param Context $context
     * @param Status $orderStatuses
     * @param array $data
     */
    public function __construct(
        Context $context,
        Status $orderStatuses,
        array $data = []
    ) {
        $this->orderStatuses = $orderStatuses;
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
        return $this->setName($value . '[]');
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
        $this->setExtraParams('multiple');
        return parent::_toHtml();
    }

    /**
     * @return array
     */
    private function getSourceOptions(): array
    {
        $orderStatusArray = $this->orderStatuses->toOptionArray();
        array_shift($orderStatusArray);
        return $orderStatusArray;
    }
}
