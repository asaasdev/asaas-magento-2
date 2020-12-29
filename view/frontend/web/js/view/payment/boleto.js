define(
  [
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
        type: 'boleto',
        component: 'Asaas_Magento2/js/view/payment/method-renderer/boleto-method'
      }
    );
    return Component.extend({});
  }
);