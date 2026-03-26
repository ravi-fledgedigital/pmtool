<?php

namespace OnitsukaTiger\Customer\Model\Config\Source;

use Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory;
use Magento\Framework\DB\Ddl\Table;

/**
 * Custom Attribute Renderer
 */

class Options extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource

{

    /**
     * @var OptionFactory
     */

    protected $optionFactory;

    /**
     * @param OptionFactory $optionFactory
     */

    /**
     * Get all options
     *
     * @return array
     */

    public function getAllOptions()
    {

        $this->_options = [
            ['label'=>__('Address type'), 'value'=>''],
            ['label'=> __('home'), 'value'=>'1'],
            ['label'=> __('Office'), 'value'=>'2'],
            ['label'=> __('Apartment'), 'value'=>'3'],
            ['label'=> __('Business'), 'value'=>'4']
        ];

        return $this->_options;
    }
}
