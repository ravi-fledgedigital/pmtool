define([
    'jquery',
    'Mirasvit_LayeredNavigation/js/action/apply-filter',
    // 'jquery-ui-modules/mouse',
    'Mirasvit_LayeredNavigation/js/lib/jquery.ui.slider',
    'Mirasvit_LayeredNavigation/js/lib/jquery.ui.touch-punch',
    'domReady!'
], function ($, applyFilter) {
    'use strict';

    $.widget('mst.navSliderRenderer', {
        options: {
            paramTemplate: '',
            urlTemplate:   '',
            min:           0,
            max:           0,
            from:          0,
            to:            0,
            valueTemplate: '',
            separator:     ':',
            rate:          1,
            step:          1
        },

        from: null,
        to:   null,

        $text:   null,
        $slider: null,
        $from:   null,
        $to:     null,
        $submit: null,

        _create: function () {
            this.$text = $('[data-element = text]', this.element);
            this.$slider = $('[data-element = slider]', this.element);
            this.$from = $('[data-element = from]', this.element);
            this.$to = $('[data-element = to]', this.element);
            this.$submit = $('[data-element = submit]', this.element);

            this.from = Math.floor((this.options.from || this.options.min) * this.options.rate);
            this.to = Math.ceil((this.options.to || this.options.max) * this.options.rate);

            if (this.options.min !== this.options.max) {
                this.$slider.slider({
                    range:  true,
                    min:    this.getMin(),
                    max:    this.getMax(),
                    values: [this.from, this.to],
                    slide:  this.onSlide.bind(this),
                    change: this.onSliderChange.bind(this),
                    step:   this.options.step
                });
                const $handles = this.$slider.find('.ui-slider-handle');
                $handles.eq(0)
                    .attr('aria-valuemin', this.getMin())
                    .attr('aria-valuemax', this.getMax())
                    .attr('aria-valuenow', this.to)
                    .attr('aria-label', 'Set maximum value');
                $handles.eq(1)
                    .attr('aria-valuemin', this.getMin())
                    .attr('aria-valuemax', this.getMax())
                    .attr('aria-valuenow', this.from)
                    .attr('aria-label', 'Set minimum value');

            } else {
                this.$slider.remove();
                this.$slider = null;
            }

            this.$from.on('change keyup', this.onFromToChange.bind(this));
            this.$to.on('change keyup', this.onFromToChange.bind(this));

            this.$submit.on('click', this.onSubmit.bind(this));

            if (this.from || this.to) {
                this.updateFromTo();
            }
        },

        onSlide: function (event, ui) {
            this.from = ui.values[0];
            this.to = ui.values[1];

            this.updateFromTo();
        },

        onSliderChange: function (event, ui) {
            this.from = ui.values[0];
            this.to = ui.values[1];

            if (event.eventPhase) { // it's user event
                this.applyFilter();
            }
        },

        onFromToChange: function () {
            this.from = this.toFixed(parseFloat(this.$from.val()), 2);
            this.to = this.toFixed(parseFloat(this.$to.val()), 2);

            this.updateFromTo();
        },

        onSubmit: function (e) {
            e.preventDefault();
            this.applyFilter();
        },

        applyFilter: function () {
            const value = this.toFixed(this.from / this.options.rate, 2) + this.options.separator + this.toFixed(this.to / this.options.rate, 2);

            let url = this.options.urlTemplate.replace(this.options.paramTemplate, value);

            applyFilter.apply(url, $(this.element));
        },

        getMax: function() {
            return Math.ceil(this.options.max * this.options.rate);
        },

        getMin: function() {
            return Math.floor(this.options.min * this.options.rate);
        },

        updateFromTo: function () {
            this.$text.html(this.getTextValue(this.from) + ' - ' + this.getTextValue(this.to));

            this.$from.val(this.toFixed(this.from, 2));
            this.$to.val(this.toFixed(this.to, 2));

            if (this.$slider) {
                const to = this.to > this.getMax() ? this.getMax() : this.to;
                const from = this.from > to ? this.getMin() : this.from;

                this.$slider.slider('values', [this.toFixed(from, 2), this.toFixed(to, 2)]);
            }
        },

        getTextValue: function (value) {
            let tmpl = this.options.valueTemplate;

            tmpl = tmpl.replace('{value}', this.toFixed(value, 0));
            tmpl = tmpl.replace('{value.0}', this.toFixed(value, 0));
            tmpl = tmpl.replace('{value.1}', this.toFixed(value, 1));
            tmpl = tmpl.replace('{value.2}', this.toFixed(value, 2));

            return tmpl;
        },

        toFixed: function (value, precision) {
            //1.00 === 1
            if (parseFloat(parseFloat(value).toFixed(0)) === parseFloat(parseFloat(value).toFixed(precision))) {
                return parseFloat(value).toFixed(0);
            }
            return parseFloat(value).toFixed(precision);
        }
    });

    return $.mst.navSliderRenderer;
});
