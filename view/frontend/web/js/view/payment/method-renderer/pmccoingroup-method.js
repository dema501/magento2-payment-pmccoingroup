/**
 *
 * @category   Liftmode
 * @package    PMCCoinGroup
 * @copyright  Copyright (c) Dmitry Bashlov <dema50@gmail.com
 * @license    MIT
 */
/*browser:true*/
/*global define*/
define([
        'Magento_Payment/js/view/payment/cc-form',
        'jquery',
        'Magento_Payment/js/model/credit-card-validation/validator'
    ],
    function (Component, $) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Liftmode_PMCCoinGroup/payment/pmccoingroup'
            },

            getCode: function() {
                return 'pmccoingroup';
            },

            isActive: function() {
                return true;
            },

            validate: function() {
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            }
        });
    }
);
