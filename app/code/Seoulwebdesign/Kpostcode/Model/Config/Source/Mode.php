<?php
namespace Seoulwebdesign\Kpostcode\Model\Config\Source;

class Mode implements \Magento\Framework\Data\OptionSourceInterface
{
    const VERSION_POPUP = 'popup';
    const VERSION_IFRAME = 'iframe';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::VERSION_POPUP, 'label' => __('Popup')],
            ['value' => self::VERSION_IFRAME, 'label' => __('Iframe')],
        ];
    }
}
