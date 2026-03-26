<?php

namespace OnitsukaTiger\Rma\Block\Widget\Grid\Column\Renderer;

use Magento\Framework\DataObject;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\Options;
use Magento\Backend\Block\Context;
use Magento\Framework\Stdlib\BooleanUtils;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class RequestDate extends Options
{
    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    public function __construct(
        Context $context,
        TimezoneInterface $timezone,
        BooleanUtils $booleanUtils,
        array   $data = [])
    {
        $this->timezone = $timezone;
        $this->booleanUtils = $booleanUtils;
        parent::__construct($context, $data);
    }
    /**
     * @param DataObject $row
     * @return array|mixed|string|void|null
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());

        $date = $this->timezone->date(new \DateTime($value));
        $timezone = $this->timezone->getConfigTimezone() !== null ;

        if (!$timezone) {
            $date = new \DateTime($value);
        }
        return $date->format('Y-m-d H:i:s');
    }
}
