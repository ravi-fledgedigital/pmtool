<?php
namespace Seoulwebdesign\Kpostcode\Model\Config\Source;

class Version implements \Magento\Framework\Data\OptionSourceInterface
{
    const VERSION_LEGACY = 'legacy';
    const VERSION_DAUM = 'daum';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            //['value' => self::VERSION_LEGACY, 'label' => __('Legacy')],
            ['value' => self::VERSION_DAUM, 'label' => __('Daum API')],
        ];
    }
}
