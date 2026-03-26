<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\History;

use Amasty\Rma\Model\OptionSource\EventInitiator;
use Amasty\Rma\Model\OptionSource\EventType;

class ProcessMessage
{
    /**
     * @var string[]
     */
    public $events = [
        EventType::RMA_CREATED => '%1 %2 created new RMA',
        EventType::STATUS_AUTOMATICALLY_CHANGED => 'Status automatically changed from "%3" to "%4"',
        EventType::NEW_MESSAGE => '%1 %2 added chat message',
        EventType::DELETED_MESSAGE => '%1 %2 deleted chat message "%3"',
        EventType::TRACKING_NUMBER_ADDED => '%1 %2 added tracking number %3 %4',
        EventType::TRACKING_NUMBER_DELETED => '%1 %2 deleted tracking number %3 %4',
        EventType::MANAGER_ADDED_SHIPPING_LABEL => '%1 %2 attached shipping label',
        EventType::MANAGER_DELETED_SHIPPING_LABEL => '%1 %2 deleted shipping label',
        EventType::CUSTOMER_CLOSED_RMA => 'Customer closed RMA',
        EventType::SYSTEM_CHANGED_STATUS => 'Status changed from "%3" to "%4" by automation rule.',
        EventType::SYSTEM_CHANGED_MANAGER => 'Manager changed from "%3" to "%4" by automation rule.'
    ];

    /**
     * @var string[]
     */
    public $saveEvents = [
        'status' => 'Status changed from "%1" to "%2". ',
        'manager' => 'Manager changed from "%1" to "%2". ',
        'note' => 'Note changed from "%1" to "%2"',
        'item-changed' => 'Item "%1 %2" changed:',
        'state' => '- state from "%1" to "%2"',
        'qty' => '- qty from "%1" to "%2"',
        'reason' => '- reason from "%1" to "%2"',
        'condition' => '- condition from "%1" to "%2"',
        'resolution' => '- resolution from "%1" to "%2"',
        'splited' => 'Item "%1 %2" splited.
- state: %3
- qty: %4
- reason: %5
- condition: %6
- resolution: %7'
    ];

    private function addToi18n()
    {
        __('%1 %2 created new RMA');
        __('Status automatically changed from "%3" to "%4"');
        __('%1 %2 added chat message');
        __('%1 %2 deleted chat message "%3"');
        __('%1 %2 added tracking number %3 %4');
        __('%1 %2 deleted tracking number %3 %4');
        __('%1 %2 attached shipping label');
        __('%1 %2 deleted shipping label');
        __('Customer closed RMA');
        __('Status changed from "%1" to "%2". ');
        __('Manager changed from "%1" to "%2". ');
        __('Note changed from "%1" to "%2"');
        __('Item "%1 %2" changed:');
        __('- state from "%1" to "%2"');
        __('- qty from "%1" to "%2"');
        __('- reason from "%1" to "%2"');
        __('- condition from "%1" to "%2"');
        __('- resolution from "%1" to "%2"');
        __('Item "%1 %2" splited.
- state: %3
- qty: %4
- reason: %5
- condition: %6
- resolution: %7');
    }

    public function execute(\Amasty\Rma\Api\Data\HistoryInterface $event)
    {
        $data = $event->getEventData();

        array_unshift($data, $event->getEventInitiatorName());
        $who = __('System');
        switch ($event->getEventInitiator()) {
            case EventInitiator::MANAGER:
                $who = __('Manager');
                break;
            case EventInitiator::CUSTOMER:
                $who = __('Customer');
                break;
        }
        array_unshift($data, $who);

        switch ($event->getEventType()) {
            case EventType::MANAGER_SAVED_RMA:
                $event->setMessage($this->getMessageForSavedRmaByManager($data));
                break;
            default:
                if (isset($this->events[$event->getEventType()])) {
                    $event->setMessage(__($this->events[$event->getEventType()], ...$data));
                } else {
                    $event->setMessage('');
                }
                break;
        }

        return $event;
    }

    public function getMessageForSavedRmaByManager($data)
    {
        $message = '';
        if (isset($data['before']) && isset($data['after'])) {

            $before = $data['before'];
            $after = $data['after'];
            if (isset($before['status']) && isset($after['status'])) {
                $message .= __($this->saveEvents['status'], $before['status'], $after['status']) . "\n";
            }

            if (isset($before['manager']) && isset($after['manager'])) {
                $message .= __($this->saveEvents['manager'], $before['manager'], $after['manager']) . "\n";
            }

            if (isset($before['note']) && isset($after['note'])) {
                $message .= __($this->saveEvents['note'], $before['note'], $after['note']) . "\n";
            }

            $message .= "\n";

            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $message .= __($this->saveEvents['item-changed'], $item['name'], $item['sku']) . "\n";
                    foreach (['state', 'qty', 'reason', 'condition', 'resolution'] as $itemParam) {
                        if (!empty($item['before'][$itemParam]) && !empty($item['after'][$itemParam])) {
                            $message .= __(
                                $this->saveEvents[$itemParam],
                                $item['before'][$itemParam],
                                $item['after'][$itemParam]
                            ) . "\n";
                        }
                    }
                }
                $message .= "\n";
            }

            if (!empty($data['splited'])) {
                foreach ($data['splited'] as $item) {
                    $message .= __(
                        $this->saveEvents['splited'],
                        $item['name'],
                        $item['sku'],
                        $item['state'],
                        $item['qty'],
                        $item['reason'],
                        $item['condition'],
                        $item['resolution']
                    ) . "\n";
                }
            }
        }

        return $message;
    }
}
