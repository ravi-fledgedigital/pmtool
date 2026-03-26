/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */


define([
    'jquery',
    'mage/storage',
    'uiRegistry'
], function ($, storage, reg) {
    'use strict';

    return function (modal) {

        return modal.extend({

            actionRun: function () {
                this.isNotice(false);
                this.isError(false);
                $(".debug").html('');
                var job = reg.get(this.job).data.entity_id;
                if (job == '') {
                    job = localStorage.getItem('jobId');
                    this.isJob = 1;
                }
                var berforeUrl = this.beforeUrl + '?id=' + job;
                var ajaxSend = this.ajaxSend.bind(this);
                this.loading(true);
                this.getFile(berforeUrl).then(ajaxSend);
            },

            ajaxSend: function (file) {
                this.file = file;
                this.end = 0;
                this.counter = 0;
                var job = reg.get(this.job).data.entity_id;
                if (localStorage.getItem('jobId')) {
                    job = localStorage.getItem('jobId');
                }
                var object = reg.get(this.name + '.debugger.debug');
                object.percent(0);
                object.percentWidth('0%');
                var url = this.url +     '?form_key='+ window.FORM_KEY;
                url +=  '&id=' + job + '&file=' + file;
                this.currentAjax = this.urlAjax + '?file=' + file;
                var urlAjax = this.currentAjax;
                this.runUrl = url;

                $('.run').attr("disabled", true);
                var self = this;

                var useApi = reg.get(this.ns + '.' + this.ns+'.settings.use_api'),
                    platform = reg.get(this.ns + '.' + this.ns + '.settings.platforms');

                if((useApi.value() == 1)
                    && ((platform.value() == 'netsuiteProduct')
                        || (platform.value() == 'netsuiteCustomer')
                        || (platform.value() == 'netsuiteCategory')
                        || (platform.value() == 'netsuiteOrder'))){
                    this.currentPage = 1;
                }

                this.import();

                if (self.end != 1) {
                    setTimeout(self.getDebug.bind(self, urlAjax), 3500);
                }
            },

            setData:function (counter, count, error, job, file, step, object) {
                var  self = this,
                    urlData = self.urlProcess + '?form_key=' + window.FORM_KEY + '&number=' + count + '&job=' + job + '&file=' + file +'&error=' + error,
                    reindexUrl = self.reindexUrl +'?job=' + job + '&file=' + file,
                    reindex = reg.get(this.ns + '.' + this.ns+'.general.reindex');
                if (count <= counter - 1) {
                    $.get(
                        urlData
                    ).done(
                        function (response) {
                            var percent = Math.round(object.percent() * 100) / 100 + step;
                            object.percent(percent);
                            object.percentWidth(percent + '%');
                            if (response.result == true) {
                                self.setData(counter, count + 1, parseInt(response.count), job, file, step, object);
                            } else {
                                if (reindex.value() == 1) {
                                    self.reindex(reindexUrl);
                                }
                                self.finish(false);
                            }
                        }
                    ).fail(
                        function (response) {
                            self.finish(false);
                            self.error(response.responseText);
                        }
                    );
                } else {
                    var useApi = reg.get(this.ns + '.' + this.ns+'.settings.use_api'),
                        platform = reg.get(this.ns + '.' + this.ns + '.settings.platforms');

                    if((useApi.value() == 1)
                        && ((platform.value() == 'netsuiteProduct')
                            || (platform.value() == 'netsuiteCustomer')
                            || (platform.value() == 'netsuiteCategory')
                            || (platform.value() == 'netsuiteOrder'))){
                        this.import();
                    }else{
                        if (reindex.value() == 1) {
                            return self.reindex(reindexUrl);
                        } else {
                            self.finish(true);
                            return true;
                        }
                    }
                }

                return true;
            },

            import: function () {
                var object = reg.get(this.name + '.debugger.debug'),
                    runUrl = this.runUrl,
                    useApi = reg.get(this.ns + '.' + this.ns+'.settings.use_api'),
                    platform = reg.get(this.ns + '.' + this.ns + '.settings.platforms'),
                    job = reg.get(this.job).data.entity_id,
                    file = this.file,
                    self = this,
                    reindexUrl = self.reindexUrl +'?job=' + job + '&file=' + file,
                    reindex = reg.get(this.ns + '.' + this.ns+'.general.reindex'),
                    startPage = reg.get(this.ns + '.' + this.ns + '.source.catalog_product_netsuiteProduct_start_page');

                if (localStorage.getItem('jobId')) {
                    job = localStorage.getItem('jobId');
                }

                if((useApi.value() == 1)
                    && ((platform.value() == 'netsuiteProduct')
                        || (platform.value() == 'netsuiteCustomer')
                        || (platform.value() == 'netsuiteCategory')
                        || (platform.value() == 'netsuiteOrder'))){
                    runUrl += '&offset=' + this.currentPage;
                    if (startPage.value() !== '1' && this.currentPage == 1) {
                        this.currentPage = startPage.value();
                    } else {
                        this.currentPage = +this.currentPage + 1;
                    }
                }

                storage.get(
                    runUrl
                ).done(
                    function (response) {
                        if (response.result != false) {
                            object.value(true);
                            var url = self.urlCheck + '?form_key='+ window.FORM_KEY + '&job=' + job + '&file=' + file;
                            $.get(url).done(function (response) {
                                if (response.result > 0) {
                                    var urls = [];
                                    object.percent(10);
                                    object.percentWidth('10%');
                                    var step = Math.round((80 / response.result)*100)/100;
                                    var finish = false;
                                    if (response.result > 0) {
                                        finish = self.setData(response.result, 0, 0, job, file, step, object);
                                    }
                                } else {
                                    self.finish(true);
                                    return true;
                                }
                            }).fail(
                                function (response) {
                                    object.value(false);
                                    self.finish(false);
                                    self.error(response.responseText);
                                }
                            );
                            self.isError(false);
                        } else {
                            if (reindex.value() == 1) {
                                self.reindex(reindexUrl);
                            } else {
                                self.finish(true);
                            }
                            object.value(false);
                        }
                    }
                ).fail(
                    function (response) {
                        self.finish(false);
                        self.error(response.responseText);
                    }
                );
            },

            reindex: function (reindexUrl) {
                var self = this;
                storage.get(
                    reindexUrl
                ).done(
                    function (response) {
                        if (response.result) {
                            self.finish(true);

                            return true;
                        } else {
                            self.finish(false);

                            return false;
                        }
                    }
                ).fail(
                    function (response) {
                        self.finish(false);
                        return false;
                    }
                );
            }
        });
    }
});
