<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Model;

class ForceWhitelistRegistry
{
    /**
     * @var array
     */
    private array $disabledList = [];

    /**
     * Temporary disable model load Admin Role check
     *
     * @param string $entityClassName
     * @return void
     */
    public function forceAllowLoading(string $entityClassName): void
    {
        $this->disabledList[$entityClassName] = $this->disabledList[$entityClassName] ?? 0;
        $this->disabledList[$entityClassName] += 1;
    }

    /**
     * Enable temporary disabled check back
     *
     * @param string $entityClassName
     * @return void
     */
    public function restore(string $entityClassName): void
    {
        if (isset($this->disabledList[$entityClassName])) {
            $this->disabledList[$entityClassName] -= 1;
        }
    }

    /**
     * Checking is there any force allow for the model
     *
     * @param object $model
     * @return bool
     */
    public function isLoadingForceAllowed(object $model): bool
    {
        foreach ($this->disabledList as $class => $counter) {
            if ($counter <= 0) {
                continue;
            }

            if ($model instanceof $class) {
                return true;
            }
        }

        return false;
    }
}
