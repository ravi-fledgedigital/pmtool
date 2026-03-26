<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Setup\Operation;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Amasty\Base\Model\Serializer;
use Amasty\Rma\Model\ConfigProvider;

class UpgradeTo220
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var WriterInterface
     */
    private $writerInterface;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var TypeListInterface
     */
    private $typeList;

    public function __construct(
        WriterInterface $writerInterface,
        ConfigProvider $configProvider,
        Serializer $serializer,
        TypeListInterface $typeList
    ) {
        $this->writerInterface = $writerInterface;
        $this->configProvider = $configProvider;
        $this->serializer = $serializer;
        $this->typeList = $typeList;
    }

    public function execute()
    {
        $this->addQuickReplies();
    }

    /**
     * Added quick replies in config
     */
    private function addQuickReplies()
    {
        $dataForSave = [];
        $quickReplies = array_merge($this->getQuickReplies(), $this->configProvider->getQuickReplies());

        foreach ($quickReplies as $key => $quickReply) {
            $dynamicKey = '_' . time() . '_' . rand(100, 999); // key for dynamic row
            $dataForSave[$dynamicKey]['label'] = $key;
            $dataForSave[$dynamicKey]['reply'] = $quickReply;
        }
        $quickReplyPath = 'amrma/' . ConfigProvider::QUICK_REPLIES;
        $this->writerInterface->save($quickReplyPath, $this->serializer->serialize($dataForSave));
        $this->typeList->invalidate(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
    }

    /**
     * @return array
     */
    private function getQuickReplies()
    {
        return [
            'New Request' => 'Thank you for your request! Our manager will contact you soon.',
            'Need Details' => 'Please, provide us with additional details and'
                    . ' attach photos if possible so that we could approve your request.',
            'Approved' => 'Your request has been approved!',
            'Resolved' => 'Your return request is successfully resolved!'
                    . ' Please, rate our service so that we could improve it. Thanks!'
        ];
    }
}
