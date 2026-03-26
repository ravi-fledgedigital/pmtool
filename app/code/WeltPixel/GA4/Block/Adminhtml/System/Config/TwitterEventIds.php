<?php
namespace WeltPixel\GA4\Block\Adminhtml\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Config\Block\System\Config\Form\Field;
use WeltPixel\GA4\Model\Config\Source\XPixel\TrackingEvents;

/**
 * Class TwitterEventIds
 */
class TwitterEventIds extends Field
{
    /**
     * @var TrackingEvents
     */
    protected $trackingEvents;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param TrackingEvents $trackingEvents
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        TrackingEvents $trackingEvents,
        array $data = []
    ) {
        $this->trackingEvents = $trackingEvents;
        parent::__construct($context, $data);
    }

    /**
     * Render element
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $html = '<div class="admin__field-control">';
        $html .= $this->_renderTable($element);
        $html .= '</div>';

        return $html;
    }

    /**
     * Render table with events and input fields
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _renderTable(AbstractElement $element)
    {
        $html = '<table class="admin__control-table" id="' . $element->getHtmlId() . '">';

        // Table header
        $html .= '<thead><tr>';
        $html .= '<th>' . __('Event') . '</th>';
        $html .= '<th>' . __('Twitter ID') . '</th>';
        $html .= '</tr></thead>';

        // Table body
        $html .= '<tbody>';

        // Get all events
        $events = [];

        $trackingEvents = $this->trackingEvents->toOptionArray();
        foreach ($trackingEvents as $event) {
            $events[$event['value']] = $event['label'];
        }

        // Get existing values
        $values = $element->getValue();
        if (is_string($values)) {
            try {
                $values = json_decode($values, true);
            } catch (\Exception $e) {
                $values = [];
            }
        }

        if (!is_array($values)) {
            $values = [];
        }

        // Create a lookup array for existing values
        $existingValues = [];
        foreach ($values as $row) {
            if (isset($row['event']) && isset($row['twitter_id'])) {
                $existingValues[$row['event']] = $row['twitter_id'];
            }
        }

        // Render rows for each event
        $index = 0;
        foreach ($events as $eventValue => $eventLabel) {
            $twitterId = isset($existingValues[$eventValue]) ? $existingValues[$eventValue] : '';

            $html .= '<tr>';

            // Event cell
            $html .= '<td>' . $eventLabel;
            $html .= '<input type="hidden" name="' . $element->getName() . '[' . $index . '][event]" value="' . $eventValue . '" />';
            $html .= '</td>';

            // Twitter ID cell
            $html .= '<td><textarea  class="admin__control-text" name="' . $element->getName() . '[' . $index . '][twitter_id]" rows="1" >' . $this->escapeHtml($twitterId)  . '</textarea></td>';

            $html .= '</tr>';
            $index++;
        }

        $html .= '</tbody></table>';

        return $html;
    }
}
