<?php

namespace OnitsukaTiger\Reindex\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Reindex
 * @package OnitsukaTiger\Reindex\Block\Adminhtml\System\Config
 */
class Reindex extends Field
{
    /**
     * @var string
     */
    protected $_template = 'OnitsukaTiger_Reindex::system/config/reindex.phtml';

    /**
     * Geoip constructor.
     *
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Remove scope label
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Return ajax url for collect button
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('onitsuka/system/run');
    }

    /**
     * Generate collect button html
     *
     * @return mixed
     * @throws LocalizedException
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(Button::class)
            ->setData([
                'id'    => 'reindex_button',
                'label' => __('Reindex Product'),
            ]);

        return $button->toHtml();
    }

}
