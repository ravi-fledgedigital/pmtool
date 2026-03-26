<?php
namespace Seoulwebdesign\KakaoSync\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Disconnect extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Seoulwebdesign_KakaoSync::system/config/button/disconnect.phtml';

    /**
     * Remove scope label
     *
     * @param  AbstractElement $element
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
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Return url
     *
     * @return string
     */
    public function getDisconnectUrl()
    {
        return $this->getUrl('kakaosync/customer/disconnectAll');
    }

    /**
     * Generate auth code
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            [
                'id' => 'disconnect_button',
                'label' => __('Disconnect all customer'),
            ]
        );

        return $button->toHtml();
    }
}
