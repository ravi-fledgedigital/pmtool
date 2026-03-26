<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Model\PdfLib;

use Amasty\PDFCustom\Model\ConfigProvider;

class PdfLibProvider
{
    /**
     * @var PdfInterfaceFactory[]
     */
    private $pdfLibraries;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        ConfigProvider $configProvider,
        array $pdfLibraries = []
    ) {
        $this->configProvider = $configProvider;
        $this->pdfLibraries = $pdfLibraries;
    }

    public function get(?string $type = null): PdfInterface
    {
        if (empty($type)) {
            $type = $this->configProvider->getLibraryCode();
        }

        if (!isset($this->pdfLibraries[$type])) {
            throw new \InvalidArgumentException(sprintf('Unknown pdf library "%s"', $type));
        }

        return $this->pdfLibraries[$type]->create();
    }
}
