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
 * @package   mirasvit/module-cataloglabel
 * @version   2.5.7
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\CatalogLabel\Model;

use Magento\Framework\DataObject;
use Magento\Framework\DataObject\IdentityInterface;
use Mirasvit\CatalogLabel\Api\Data\PlaceholderInterface;
use Magento\Framework\Model\AbstractModel;

class Placeholder extends AbstractModel implements IdentityInterface, PlaceholderInterface
{
    /**
     * @var string
     */
    protected $_cacheTag = 'cataloglabel_placeholder';
    /**
     * @var string
     */
    protected $_eventPrefix = 'cataloglabel_placeholder';

    /**
     * Get identities.
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG.'_'.$this->getId()];
    }

    /**
     * {@inheritDoc}
     */
    protected function _construct()
    {
        $this->_init('Mirasvit\CatalogLabel\Model\ResourceModel\Placeholder');
    }

    public function getName(): string
    {
        return (string)$this->getData(self::NAME);
    }

    public function setName(string $value): PlaceholderInterface
    {
        return $this->setData(self::NAME, $value);
    }

    public function getCode(): string
    {
        return (string)$this->getData(self::CODE);
    }

    public function setCode(string $value): PlaceholderInterface
    {
        return $this->setData(self::CODE, $value);
    }

    public function getIsActive(): bool
    {
        return (bool)$this->getData(self::IS_ACTIVE);
    }

    public function setIsActive(bool $value): PlaceholderInterface
    {
        return $this->setData(self::IS_ACTIVE, $value);
    }

    public function getPosition(): string
    {
        return (string)$this->getData(self::POSITION);
    }

    public function setPosition(string $value): PlaceholderInterface
    {
        return $this->setData(self::POSITION, $value);
    }

    public function getLabelsLimit(): int
    {
        return (int)$this->getData(self::LABELS_LIMIT);
    }

    public function setLabelsLimit(int $value): PlaceholderInterface
    {
        return $this->setData(self::LABELS_LIMIT, $value);
    }

    public function getLabelsDirection(): string
    {
        return (string)$this->getData(self::LABELS_DIRECTION);
    }

    public function setLabelsDirection(string $value): PlaceholderInterface
    {
        return $this->setData(self::LABELS_DIRECTION, $value);
    }
}
