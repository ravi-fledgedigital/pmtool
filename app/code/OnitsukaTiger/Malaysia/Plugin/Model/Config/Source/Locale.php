<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Malaysia\Plugin\Model\Config\Source;

/**
 * Class Locale
 * @package OnitsukaTiger\Malaysia\Plugin\Model\Config\Source
 */
class Locale
{
    /**
     * @param \Magento\Config\Model\Config\Source\Locale $subject
     * @param array $result
     * @return array
     */
    public function aftertoOptionArray(\Magento\Config\Model\Config\Source\Locale $subject, array  $result)
    {
        $newresult = $result;
        $enMyLocale = [
            'value'=>'en_MY',
            'label' => 'Malaysia ( English )'
        ];
        $newresult[] = $enMyLocale;
        return $newresult;
    }

}
