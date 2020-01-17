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
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';

        rendererList.push(
            {
                type: 'pmccoingroup',
                component: 'Liftmode_PMCCoinGroup/js/view/payment/method-renderer/pmccoingroup-method'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
