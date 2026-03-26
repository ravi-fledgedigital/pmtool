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
 * @package   mirasvit/module-landing-page
 * @version   1.1.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\LandingPage\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Mirasvit\LandingPage\Api\Data\FilterInterface;

class Filter extends AbstractModel implements IdentityInterface, FilterInterface
{

    const CACHE_TAG = 'mst_landing_page_filter';

    protected $_cacheTag    = 'mst_landing_page_filter';

    protected $_eventPrefix = 'mst_landing_page_filter';

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getPageId(): int
    {
        return (int)$this->getData(FilterInterface::PAGE_ID);
    }

    public function setPageId(int $value): self
    {
        return $this->setData(FilterInterface::PAGE_ID, $value);
    }

    public function getAttributeId(): int
    {
        return (int)$this->getData(FilterInterface::ATTRIBUTE_ID);
    }

    public function setAttributeId(int $value): FilterInterface
    {
        return $this->setData(FilterInterface::ATTRIBUTE_ID, $value);
    }

    public function getAttributeCode(): string
    {
        return $this->getData(FilterInterface::ATTRIBUTE_CODE);
    }

    public function setAttributeCode(string $value): FilterInterface
    {
        return $this->setData(FilterInterface::ATTRIBUTE_CODE, $value);
    }

    public function getOptionIds(): string
    {
        return (string)$this->getData(FilterInterface::OPTION_IDS);
    }

    public function setOptionIds(string $value): FilterInterface
    {
        return $this->setData(FilterInterface::OPTION_IDS, $value);
    }

    protected function _construct()
    {
        $this->_init('Mirasvit\LandingPage\Model\ResourceModel\Filter');
    }

}
