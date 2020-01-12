define([
        'jquery',
        'Magento_Payment/js/view/payment/cc-form'
    ],
    function ($, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Liftmode_PMCCoinGroup/payment/pmccoingroup'
            },

            context: function() {
                return this;
            },

            getCode: function() {
                return 'liftmode_pmccoingroup';
            },

            isActive: function() {
                return true;
            }
        });
    }
);