<?php
namespace Seoulwebdesign\Toast\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Seoulwebdesign\Toast\Model\Message;

class Status implements OptionSourceInterface
{
    /**
     * @var Message
     */
    protected $message;

    /**
     * Constructor
     *
     * @param Message $message
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options[] = ['label' => '', 'value' => ''];
        $availableOptions = $this->message->getAvailableStatuses();
        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }
        return $options;
    }
}
