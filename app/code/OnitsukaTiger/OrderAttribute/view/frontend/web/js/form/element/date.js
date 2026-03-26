define([
    'jquery',
    'ko',
    'underscore',
    'mageUtils',
    'Magento_Ui/js/form/element/date',
    'OnitsukaTiger_OrderAttribute/js/form/relationAbstract',
    'mage/calendar'
], function ($, ko, _, utils, DateForm, relationAbstract) {
    'use strict';

    var timePickerPrototype = $.timepicker.constructor.prototype;

    /**
     * DateTimePicker open on load fix
     */
    $.extend(timePickerPrototype, {
        _onTimeChange: function () {
            if (!this._defaults.showTimepicker) {
                return;
            }
            var hour = (this.hour_slider) ? this.control.value(this, this.hour_slider, 'hour') : false,
                minute = (this.minute_slider) ? this.control.value(this, this.minute_slider, 'minute') : false,
                second = (this.second_slider) ? this.control.value(this, this.second_slider, 'second') : false,
                millisec = (this.millisec_slider) ? this.control.value(this, this.millisec_slider, 'millisec') : false,
                microsec = (this.microsec_slider) ? this.control.value(this, this.microsec_slider, 'microsec') : false,
                timezone = (this.timezone_select) ? this.timezone_select.val() : false,
                o = this._defaults,
                pickerTimeFormat = o.pickerTimeFormat || o.timeFormat,
                pickerTimeSuffix = o.pickerTimeSuffix || o.timeSuffix;

            if (typeof(hour) === 'object') {
                hour = false;
            }
            if (typeof(minute) === 'object') {
                minute = false;
            }
            if (typeof(second) === 'object') {
                second = false;
            }
            if (typeof(millisec) === 'object') {
                millisec = false;
            }
            if (typeof(microsec) === 'object') {
                microsec = false;
            }
            if (typeof(timezone) === 'object') {
                timezone = false;
            }

            if (hour !== false) {
                hour = parseInt(hour, 10);
            }
            if (minute !== false) {
                minute = parseInt(minute, 10);
            }
            if (second !== false) {
                second = parseInt(second, 10);
            }
            if (millisec !== false) {
                millisec = parseInt(millisec, 10);
            }
            if (microsec !== false) {
                microsec = parseInt(microsec, 10);
            }
            if (timezone !== false) {
                timezone = timezone.toString();
            }

            var ampm = o[hour < 12 ? 'amNames' : 'pmNames'][0];

            // If the update was done in the input field, the input field should not be updated.
            // If the update was done using the sliders, update the input field.
            var hasChanged = (
                hour !== parseInt(this.hour,10) || // sliders should all be numeric
                minute !== parseInt(this.minute,10) ||
                second !== parseInt(this.second,10) ||
                millisec !== parseInt(this.millisec,10) ||
                microsec !== parseInt(this.microsec,10) ||
                (this.timezone !== null && timezone !== this.timezone.toString()) // could be numeric or "EST" format, so use toString()
            );

            if (hasChanged) {

                if (hour !== false) {
                    this.hour = hour;
                }
                if (minute !== false) {
                    this.minute = minute;
                }
                if (second !== false) {
                    this.second = second;
                }
                if (millisec !== false) {
                    this.millisec = millisec;
                }
                if (microsec !== false) {
                    this.microsec = microsec;
                }
                if (timezone !== false) {
                    this.timezone = timezone;
                }

                if (!this.inst) {
                    this.inst = $.datepicker._getInst(this.$input[0]);
                }

                this._limitMinMaxDateTime(this.inst, true);
            }
            if (this.support.ampm) {
                this.ampm = ampm;
            }

            // Updates the time within the timepicker
            this.formattedTime = $.datepicker.formatTime(o.timeFormat, this, o);
            if (this.$timeObj) {
                if (pickerTimeFormat === o.timeFormat) {
                    this.$timeObj.text(this.formattedTime + pickerTimeSuffix);
                }
                else {
                    this.$timeObj.text($.datepicker.formatTime(pickerTimeFormat, this, o) + pickerTimeSuffix);
                }
            }

            this.timeDefined = true;
            if (hasChanged) {
                this._updateDateTime();
                this.$input.focus();
            }
        }
    });

    return DateForm.extend(relationAbstract).extend({
        isFieldInvalid: function () {
            return this.error() && this.error().length ? this : null;
        }
    });
});
