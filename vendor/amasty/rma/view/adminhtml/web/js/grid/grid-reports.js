define([
    'uiElement',
    'underscore',
    'jquery',
    'mage/translate',
    'Amasty_Rma/vendor/amcharts4/charts',
    'Amasty_Rma/vendor/amcharts4/animated',
], function (Element, _, $) {
    'use strict';

    return Element.extend({
        defaults: {
            template: 'Amasty_Rma/grid/amrma-requests-stat',
            dataUrl: '',
            admins: []
        },

        initObservable: function () {
            this._super().observe(['admins']);

            return this;
        },

        getGraphData: function () {
            $.ajax({
                url: this.dataUrl,
                method: 'GET',
                global: false,
                dataType: 'json',
                success: function (data) {
                    this.init(data);
                }.bind(this)
            });
        },

        init: function (data) {
            this.initTotalRequestsChart(data.totalByState, data.itemsBasePrice, data.basePriceFormatted);
            this.initTopReasonChart(data.topReasons);
            this.admins(data.managersTotal);
            this.totalPrice = data.itemsBasePrice;
        },

        sortFunction: function (a, b) {
            return (b.total - a.total);
        },

        initTopReasonChart: function (data) {
            var chart = am4core.create("amrma-top-chart", am4charts.XYChart),
                categoryAxis,
                valueAxis,
                series,
                valueLabel,
                columnTemplate;

            chart.maskBullets = false;
            chart.paddingBottom = 30;
            chart.paddingLeft = 5;

            data = data.sort(this.sortFunction);
            if (data.length > 5) {
                data.length = 5;
            }
            chart.data = data;

            categoryAxis = chart.xAxes.push(new am4charts.CategoryAxis());
            categoryAxis.dataFields.category = "title";
            categoryAxis.renderer.grid.template.disabled = true;
            categoryAxis.renderer.line.strokeOpacity = 1;
            categoryAxis.renderer.line.strokeWidth = 1;
            categoryAxis.renderer.line.stroke = '#b9b9b9';
            categoryAxis.renderer.labels.template.disabled = true;

            valueAxis = chart.yAxes.push(new am4charts.ValueAxis());
            valueAxis.renderer.grid.template.disabled = true;
            valueAxis.renderer.line.strokeOpacity = 1;
            valueAxis.renderer.line.strokeWidth = 1;
            valueAxis.renderer.line.stroke = '#b9b9b9';
            valueAxis.renderer.labels.template.disabled = true;

            series = chart.series.push(new am4charts.ColumnSeries());
            series.dataFields.valueY = "total";
            series.dataFields.categoryX = "title";
            series.name = "total";
            series.columns.template.height = am4core.percent(50);

            columnTemplate = series.columns.template;
            columnTemplate.column.cornerRadiusTopLeft = 10;
            columnTemplate.column.cornerRadiusTopRight = 10;
            columnTemplate.strokeWidth = 0;
            columnTemplate.fill = '#5f92f7';
            columnTemplate.tooltipText = "[fill:#fff]{categoryX}:[/] [bold;fill:#fff]{valueY}[/]";

            valueLabel = series.bullets.push(new am4charts.LabelBullet());
            valueLabel.label.text = "{total}";
            valueLabel.label.dy = -10;

            columnTemplate.adapter.add("fillOpacity", function (fill, target) {
                return fill - 0.15*target.dataItem.index
            })
        },

        initTotalRequestsChart: function (data, totalPrice, totalPriceFormatted) {
            var chart = am4core.create("amrma-total-chart", am4charts.PieChart),
                pieSeries,
                label,
                labelCounter,
                marker;

            am4core.useTheme(am4themes_animated);

            data.totalPrice = totalPrice;
            chart.paddingLeft = -20;
            chart.data = data;
            chart.innerRadius = am4core.percent(65);

            pieSeries = chart.series.push(new am4charts.PieSeries());
            pieSeries.dataFields.value = "total";
            pieSeries.dataFields.category = "name";
            pieSeries.labels.template.disabled = true;
            pieSeries.ticks.template.disabled = true;
            pieSeries.slices.template.tooltipText = "[fill:#fff]{category}:[/] [bold;fill:#fff]{value.value}[/]";

            label = pieSeries.createChild(am4core.Label);
            label.text = totalPriceFormatted;
            label.verticalCenter = "top";
            label.horizontalCenter = "middle";
            label.fontSize = 14;

            labelCounter = pieSeries.createChild(am4core.Label);
            labelCounter.text = "[bold]{values.value.sum}[/]";
            labelCounter.verticalCenter = "bottom";
            labelCounter.horizontalCenter = "middle";
            labelCounter.fontSize = 20;

            pieSeries.hiddenState.properties.opacity = 1;
            pieSeries.hiddenState.properties.endAngle = -90;
            pieSeries.hiddenState.properties.startAngle = -90;

            chart.legend = new am4charts.Legend();
            chart.legend.position = 'left';
            chart.legend.labels.template.text = "[font-size:14px]{category}[/]";
            chart.legend.valueLabels.template.text = "[bold;font-size:14px]{value.value}[/]";
            chart.legend.itemContainers.template.paddingTop = 7;
            chart.legend.itemContainers.template.paddingBottom = 7;

            //temporarily disable clickability
            chart.legend.itemContainers.template.clickable = false;
            chart.legend.itemContainers.template.focusable = false;
            chart.legend.itemContainers.template.cursorOverStyle = am4core.MouseCursorStyle.default;

            marker = chart.legend.markers.template.children.getIndex(0);
            marker.cornerRadius(9, 9, 9, 9);
            marker.width = 18;
            marker.height = 18;
        }
    });
});
