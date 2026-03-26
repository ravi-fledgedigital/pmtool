<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Block\Adminhtml\Settings\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\App\ObjectManager;

class QuickReplies extends AbstractFieldArray
{
    /**
     * @var Elements\Textarea
     */
    private $textareaRenderer;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        Elements\Textarea $textareaRenderer,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->textareaRenderer = $textareaRenderer;
    }

    protected function _prepareToRender()
    {
        $this->addColumn(
            'label',
            ['label' => __('Label'), 'size' => 3, 'class' => 'required-entry']
        );
        $this->addColumn(
            'reply',
            [
                'label' => __('Quick Reply'),
                'size' => 3,
                'class' => 'required-entry',
                'renderer' => $this->textareaRenderer
            ]
        );
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}
