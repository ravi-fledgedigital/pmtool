<?php
namespace Seoulwebdesign\Toast\Model\Resolver;

use Magento\Store\Model\StoreManagerInterface;

class VarShopName
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }
    /**
     * Main execute
     *
     * @param \Seoulwebdesign\Toast\Model\Message $message
     * @param array $data
     * @return mixed
     */
    public function execute($message, $data)
    {
        try {
            return $this->storeManager->getStore()->getName();
        } catch (\Throwable $t) {
            return null;
        }
    }
}
