var Statistics = {
    chartConfig : {
        type: 'line',
        data: {
            labels: [],
            datasets: []
        },
        options: {
            responsive: true,
            title: {
                display: true,
                fontSize: 14,
                text: 'SendGrid Statistics'
            },
            tooltips: {
                mode: 'label',
                callbacks: { }
            },
            hover: {
                mode: 'dataset'
            },
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 10
                },
            },
            scales: {
                xAxes: [{
                    display: true,
                    scaleLabel: {
                        show: true,
                        labelString: 'Date'
                    }
                }],
                yAxes: [{
                    display: true,
                    scaleLabel: {
                        show: true,
                        labelString: 'Events'
                    },
                    ticks: {
                        suggestedMin: 0,
                        suggestedMax: 50,
                    }
                }]
            }
        }
    },
    context : null,
    colors : {
        "unique_opens" : "#fbe500",
        "opens" : "#028690",
        "unique_clicks" : "#bcd0d1",
        "clicks" : "#59c1ca",
        "delivered" : "#bcd514",
        "bounces" : "#c042be",
        "unsubscribes" : "#3e44c0",
        "requests" : "#246201",
        "spam_reports" : "#e04427",
        "spam_report_drops" : "#d59f7f",
        "blocks" : "#aa0202",
        "unsubscribe_drops" : "#6dc2f9",
        "invalid_emails" : "#6b6b6b",
        "bounce_drops" : "#ff748c"
    },
    baseUrl : null,
    localJquery : null,
    startDate : null,
    endDate : null,
    singlesends : null,
    daysBefore : 7,

    init : function () {
        this.singlesends = "all";
        this.initDates();
        this.baseUrl = jQuery("#statistics-base-url").val();

        this.context = document.getElementById("sg_stats_canvas").getContext("2d");
        window.chart = new Chart(this.context, this.chartConfig);

        jQuery("#sendgrid-stats-apply").click(
            jQuery.proxy(
                function () {
                    this.startDate = jQuery("#sendgrid-start-date").val();
                    this.endDate = jQuery("#sendgrid-end-date").val();
                    this.singlesends = jQuery("#sendgrid-singlesends").val();

                    this.doRequest();
                },
                this
            )
        );

        this.doRequest();
    },

    doRequest: function () {
        jQuery("#sendgrid_error").hide();
        jQuery("#sendgrid_stats_load").show();

        jQuery.ajax({
            url: this.baseUrl,
            context: this,
            async: true,
            method: "GET",
            data: {form_key: window.FORM_KEY, start: this.startDate, end: this.endDate, singlesend: this.singlesends},
            success: this.update,
            error: jQuery.proxy(function () {
                this.displayError();
            }, this)
        });
    },

    update: function (response) {
        var stats = JSON.parse(response);

        if (!stats.dates.length) {
            this.displayError();
        }

        this.chartConfig.data.labels = stats.dates;
        this.chartConfig.data.datasets = [];
        for (var key in stats.metrics) {
            if (typeof this.colors[key] != 'undefined' ) {
                var set = {
                    label: stats.metrics[key].label,
                    data: stats.metrics[key].values,
                    lineTension: 0,
                    fill: false,
                    borderColor: this.colors[key],
                    backgroundColor: this.colors[key],
                    pointBorderColor: this.colors[key],
                    pointBackgroundColor: this.colors[key],
                    pointBorderWidth: 1
                };

                this.chartConfig.data.datasets.push(set);
            }
        }

        window.chart.update();
        jQuery("#sendgrid_stats_load").hide();
    },

    initDates: function () {
        var date = new Date();

        jQuery("#sendgrid-start-date").datepicker({
            dateFormat: "yy-mm-dd",
            changeMonth: true,
            maxDate: this.dateToYmd(new Date()),
            onClose: jQuery.proxy(function ( selectedDate ) {
                jQuery("#sendgrid-end-date").datepicker("option", "minDate", selectedDate);
            }, this)
        });
        initialStartDate = new Date(date.getFullYear(), date.getMonth(), date.getDate() - this.daysBefore);
        jQuery("#sendgrid-start-date").datepicker("setDate", initialStartDate);
        this.startDate = this.dateToYmd(initialStartDate);

        jQuery("#sendgrid-end-date").datepicker({
            dateFormat: "yy-mm-dd",
            changeMonth: true,
            maxDate: this.dateToYmd(new Date()),
            onClose: jQuery.proxy(function (selectedDate) {
                jQuery("#sendgrid-start-date").datepicker("option", "maxDate", selectedDate);
            }, this)
        });
        initialEndDate = new Date(date.getFullYear(), date.getMonth(), date.getDate());
        jQuery("#sendgrid-end-date").datepicker("setDate", initialEndDate);
        this.endDate = this.dateToYmd(initialEndDate);
    },

    dateToYmd: function (date) {
        var d = date.getDate(),
            m = date.getMonth() + 1,
            y = date.getFullYear();

        return "" + y + "-" + (m <= 9 ? "0" + m : m) + "-" + (d <= 9 ? "0" + d : d);
    },

    displayError: function () {
        jQuery("#sendgrid_stats_load").hide();
        jQuery("#sendgrid_error").show();
    }
};
