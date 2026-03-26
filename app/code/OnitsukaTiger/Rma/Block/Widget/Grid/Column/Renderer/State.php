<?php

namespace OnitsukaTiger\Rma\Block\Widget\Grid\Column\Renderer;

use Magento\Framework\DataObject;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\Options;
use Amasty\Rma\Model\OptionSource\State as RmaState;
use Magento\Backend\Block\Context;

class State extends Options
{
    /**
     * @var RmaState
     */
    private $rmaState;

    /**
     * @param Context $context
     * @param RmaState $rmaState
     * @param array $data
     */

    public function __construct(
        Context $context,
        RmaState $rmaState,
        array   $data = [])
    {
        $this->rmaState = $rmaState;
        parent::__construct($context, $data);
    }
    /**
     * @param DataObject $row
     * @return array|mixed|string|void|null
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        $result = $this->rmaState->toArray();
        return $result[$value];
    }
}
