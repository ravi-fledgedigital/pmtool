<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-sorting
 * @version   1.4.5
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Sorting\Plugin\SearchAutocomplete\InstantProvider\ConfigMaker;

use Mirasvit\SearchAutocomplete\InstantProvider\ConfigMaker as Subject;
use Mirasvit\Sorting\Model\ConfigProvider;

class AppendSortingConfig
{

    private ConfigProvider $defaultConfigProvider;

    public function __construct(
        ConfigProvider $defaultConfigProvider
    ) {
        $this->defaultConfigProvider = $defaultConfigProvider;
    }

    public function afterEmitConfigCreation(Subject $subject, array $config): array
    {
        $sortingConfig = [
            'sorting_default' => $this->defaultConfigProvider->getAutocompleteDefaultSortingConfig()
        ];

        return $this->setConfigValue(
            $config,
            'sorting_config',
            $sortingConfig
         );
    }

    private function setConfigValue(
        array $configData,
        string $path,
        array $value
    ): array {
        $configData["0/$path"] = $value;

        return $configData;
    }
}
