<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServer\Plugin;

use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Framework\DataObject;
use Magento\Framework\MessageQueue\PoisonPill\PoisonPillPutInterface;

class RefreshState
{

    /**
     * @var PoisonPillPutInterface
     */
    private $poisonPillPut;

    /**
     * @param PoisonPillPutInterface $poisonPillPut
     */
    public function __construct(PoisonPillPutInterface $poisonPillPut)
    {
        $this->poisonPillPut = $poisonPillPut;
    }

    /**
     * Poison Pill Put after save
     *
     * @param Attribute $subject
     * @param mixed $result
     * @param DataObject $object
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterAfterSave(Attribute $subject, $result, DataObject $object): void
    {
        $this->poisonPillPut->put();
    }
}
