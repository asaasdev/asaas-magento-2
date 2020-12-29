define(
  [
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
  ],
  function (
    Component,
    rendererList,
    type,
  ) {
    'use strict';
    rendererList.push(
      {
        type: 'cc',
        component: 'Asaas_Magento2/js/view/payment/method-renderer/cc-method'
      }
    );
    return Component.extend({});
  }
);