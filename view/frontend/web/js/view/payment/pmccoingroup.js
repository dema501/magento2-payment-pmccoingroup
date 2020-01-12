define([
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';

        rendererList.push(
            {
                type: 'liftmode_pmccoingroup',
                component: 'Liftmode_PMCCoinGroup/js/view/payment/method-renderer/pmccoingroup'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    });
