define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/shipping-rates-validator',
        'Magento_Checkout/js/model/shipping-rates-validation-rules',
        '../model/shipping-rates-validator/tablerate',
        '../model/shipping-rates-validation-rules/tablerate'
    ],
    function (
        Component,
        defaultShippingRatesValidator,
        defaultShippingRatesValidationRules,
        shippingRatesValidator,
        shippingRatesValidationRules
    ) {
        'use strict';
        defaultShippingRatesValidator.registerValidator('tablerate', shippingRatesValidator);
        defaultShippingRatesValidationRules.registerRules('tablerate', shippingRatesValidationRules);
        return Component;
    }
);
