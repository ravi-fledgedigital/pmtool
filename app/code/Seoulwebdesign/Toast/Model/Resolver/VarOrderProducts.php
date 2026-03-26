<?php
namespace Seoulwebdesign\Toast\Model\Resolver;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Seoulwebdesign\Toast\Helper\Logger;
use Seoulwebdesign\Toast\Model\Message;

class VarOrderProducts
{
    /**
     * @var array|mixed
     */
    protected $attributeResolver;
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Logger $logger
     * @param array $attributeResolver
     */
    public function __construct(
        Logger $logger,
        $attributeResolver = []
    ) {
        $this->logger = $logger;
        $this->attributeResolver = $attributeResolver;
    }

    /**
     * Main execute
     *
     * @param Message $message
     * @param array $data
     * @return string|null
     */
    public function execute($message, $data)
    {
        try {
            /** @var Order $order */
            $order = $data['order'];
            $orderItems = $order->getAllItems();
            $productNames = [];
            foreach ($orderItems as $orderItem) {
                $productName = $orderItem->getName();
                if ($message->getVarOrderProductsNameExtra()) {
                    $productName .= $this->getProductNameExtra($message, $orderItem);
                }
                $productNames[] = $productName;
            }
            return implode(', ', $productNames);
        } catch (\Throwable $t) {
            return null;
        }
    }

    /**
     * Get Product Name Extra
     *
     * @param Message $message
     * @param Item $orderItem
     * @return string
     */
    protected function getProductNameExtra($message, $orderItem)
    {
        $attributes = explode(',', $message->getVarOrderProductsNameExtra());
        if ($attributes) {
            $product = $orderItem->getProduct();
            $attText = [];
            foreach ($attributes as $attribute_code) {
                $attribute_code = trim($attribute_code);
                try {
                    if (isset($this->attributeResolver[$attribute_code])) {
                        $resolver = $this->attributeResolver[$attribute_code];
                        $result = $resolver->execute($message, $orderItem);
                        if ($result) {
                            $attText[] = $result;
                        }
                    } else {
                        $attr = $product->getResource()->getAttribute($attribute_code);
                        if ($attr) {
                            $label = $product->getResource()->getAttribute($attribute_code)->getStoreLabel();
                            $value = trim($product->getAttributeText($attribute_code));
                            $value = $value ? $value : $product->getCustomAttribute($attribute_code);
                            $attText[] = $label . ': ' . $value;
                        }
                    }
                } catch (\Throwable $t) {
                    $this->logger->error($t->getMessage());
                }
            }
            return implode(' ', $attText);
        }
        return '';
    }
}
