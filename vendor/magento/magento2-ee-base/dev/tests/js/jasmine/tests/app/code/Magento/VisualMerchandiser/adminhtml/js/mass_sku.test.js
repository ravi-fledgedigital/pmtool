define([
    'Magento_VisualMerchandiser/js/mass_sku',
    'jquery'
], function (VisualMerchandiserMassSku, $) {
    'use strict';

    var model,
        container,
        assignButton,
        removeButton,
        dataSourceComponent,
        registryDeferred,
        registryDeferredPromise,
        dataSourceComponentReloadDeferred,
        dataSourceComponentReloadDeferredPromise,
        dataSourceComponentReloadedDeferred,
        dataSourceComponentReloadedDeferredPromise;

    beforeAll(function () {
        registryDeferred = $.Deferred();
        registryDeferredPromise = registryDeferred.promise();
        dataSourceComponentReloadDeferred = $.Deferred();
        dataSourceComponentReloadDeferredPromise = dataSourceComponentReloadDeferred.promise();
        dataSourceComponentReloadedDeferred = $.Deferred();
        dataSourceComponentReloadedDeferredPromise = dataSourceComponentReloadedDeferred.promise();
        model = new VisualMerchandiserMassSku();
        model.registry = {
            get: function (query, callback) {
                registryDeferredPromise.done(callback);
            }
        };
        model.options = {
            massAssignButton: '.mass-action-button'
        };
        dataSourceComponent = {
            firstLoad: true,
            on: function (event, callback) {
                if (event === 'reload') {
                    dataSourceComponentReloadDeferredPromise.done(callback);
                } else {
                    dataSourceComponentReloadedDeferredPromise.done(callback);
                }
            }
        };

        container = document.createElement('div');
        assignButton = document.createElement('button');
        removeButton = document.createElement('button');

        assignButton.setAttribute('role', 'assign');
        assignButton.setAttribute('class', 'mass-action-button');
        removeButton.setAttribute('role', 'remove');
        removeButton.setAttribute('class', 'mass-action-button');

        container.appendChild(assignButton);
        container.appendChild(removeButton);
        document.body.appendChild(container);
    });

    afterAll(function () {
        container.remove();
        model = null;
        container = null;
        assignButton = null;
        removeButton = null;
        dataSourceComponent = null;
        registryDeferred = null;
        registryDeferredPromise = null;
        dataSourceComponentReloadDeferred = null;
        dataSourceComponentReloadDeferredPromise = null;
        dataSourceComponentReloadedDeferred = null;
        dataSourceComponentReloadedDeferredPromise = null;
    });

    describe('Magento_VisualMerchandiser/js/mass_sku', function () {
        it('test _bind()', function () {
            expect(removeButton.getAttribute('disabled')).toBeNull();
            expect(assignButton.getAttribute('disabled')).toBeNull();

            model._bind();
            expect(removeButton.getAttribute('disabled')).toBe('disabled');
            expect(assignButton.getAttribute('disabled')).toBe('disabled');

            registryDeferred.resolve(dataSourceComponent);
            expect(removeButton.getAttribute('disabled')).toBe('disabled');
            expect(assignButton.getAttribute('disabled')).toBe('disabled');

            dataSourceComponentReloadDeferred.resolve();
            expect(removeButton.getAttribute('disabled')).toBe('disabled');
            expect(assignButton.getAttribute('disabled')).toBe('disabled');

            dataSourceComponentReloadedDeferred.resolve();
            expect(removeButton.getAttribute('disabled')).toBeNull();
            expect(assignButton.getAttribute('disabled')).toBeNull();
        });
    });
});
