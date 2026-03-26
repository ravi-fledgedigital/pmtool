<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Plugin\Email\Model;

use Amasty\PDFCustom\Model\LogoEncoder;
use Magento\Email\Model\AbstractTemplate;

class ConvertDefaultEmailLogo
{
    /**
     * @var LogoEncoder
     */
    private $logoEncoder;

    public function __construct(
        LogoEncoder $logoEncoder
    ) {
        $this->logoEncoder = $logoEncoder;
    }

    /**
     * @param AbstractTemplate $subject
     * @param string $result
     * @return string
     */
    public function afterGetDefaultEmailLogo(AbstractTemplate $subject, string $result): string
    {
        if ($subject->getResourceName() === \Amasty\PDFCustom\Model\ResourceModel\Template::class) {
            return $this->logoEncoder->encodeLogoToBase64($subject::DEFAULT_LOGO_FILE_ID);
        }

        return $result;
    }
}
