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

namespace Mirasvit\Sorting\Service\GraphQl;

class CategoryContextService
{
    /**
     * @var int|null
     */
    private $categoryId = null;

    /**
     * @var string|null
     */
    private $categoryUid = null;

    /**
     * @var bool Used for Elastcsuite GraphQL requests
     */
    private $pinToTopEnabled = false;

    public function setCategoryId(?int $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    public function setCategoryUid($uid): void
    {
        $this->categoryUid = $uid;
    }

    public function getCategoryId(): ?int
    {
        if ($this->categoryId) {
            return $this->categoryId;
        }

        if (!$this->categoryUid) {
            return null;
        }

        $decoded = $this->decodeUid($this->categoryUid);

        return $decoded !== null ? (int)$decoded : null;
    }

    public function setPinToTopEnabled(bool $enabled): void
    {
        $this->pinToTopEnabled = $enabled;
    }

    public function isPinToTopEnabled(): bool
    {
        return $this->pinToTopEnabled;
    }

    private function decodeUid(string $uid): ?string
    {
        $decoded = base64_decode($uid, true);

        if ($decoded === false || base64_encode($decoded) !== $uid) {
            return null;
        }

        return $decoded;
    }
}
