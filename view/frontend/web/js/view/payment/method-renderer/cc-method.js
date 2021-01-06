define(
  [
    'Magento_Payment/js/view/payment/cc-form',
    'jquery',
    'Magento_Checkout/js/action/place-order',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Payment/js/model/credit-card-validation/validator',
    'Magento_Ui/js/model/messageList',
    'Magento_Checkout/js/model/quote'
  ],
  function (Component, $, i, j, k, l, messageList, quote) {

    'use strict';

    return Component.extend({
      defaults: {
        template: 'Asaas_Magento2/payment/cc',
        creditCardType: '',
        creditCardOwnerName: '',
        creditCardExpYear: '',
        creditCardExpMonth: '',
        creditCardNumber: '',
        creditCardVerificationNumber: '',
        creditCardOwnerCpf: '',
        creditCardInstallments: '',
        creditCardMobilePhone: '',
        selectedCardType: null
      },

      validate: function () {
        var isValid;

        function validarCPF(cpf) {
          var cpf;
          var i;
          var add;
          var rev;
          cpf = cpf.replace(/[^\d]+/g, '');
          if (cpf == '') return false;
          // Elimina CPFs invalidos conhecidos	
          if (cpf.length != 11 ||
            cpf == "00000000000" ||
            cpf == "11111111111" ||
            cpf == "22222222222" ||
            cpf == "33333333333" ||
            cpf == "44444444444" ||
            cpf == "55555555555" ||
            cpf == "66666666666" ||
            cpf == "77777777777" ||
            cpf == "88888888888" ||
            cpf == "99999999999")
            return false;
          // Valida 1o digito	
          add = 0;
          for (i = 0; i < 9; i++)
            add += parseInt(cpf.charAt(i)) * (10 - i);
          rev = 11 - (add % 11);
          if (rev == 10 || rev == 11)
            rev = 0;
          if (rev != parseInt(cpf.charAt(9)))
            return false;
          // Valida 2o digito	
          add = 0;
          for (i = 0; i < 10; i++)
            add += parseInt(cpf.charAt(i)) * (11 - i);
          rev = 11 - (add % 11);
          if (rev == 10 || rev == 11)
            rev = 0;
          if (rev != parseInt(cpf.charAt(10)))
            return false;
          return true;
        }

        isValid = validarCPF(this.creditCardOwnerCpf());
        if (!isValid) {
          messageList.addErrorMessage({
            message: "CPF/CNPJ inválido"
          });
        }

        var $form = $('#' + this.getCode() + '-form');
        return $form.validation() && $form.validation('isValid') && isValid;
      },

      initObservable: function () {
        this._super()
          .observe([
            'creditCardType',
            'creditCardExpYear',
            'creditCardExpMonth',
            'creditCardNumber',
            'creditCardVerificationNumber',
            'creditCardOwnerName',
            'creditCardOwnerCpf',
            'creditCardInstallments',
            'creditCardMobilePhone',
            'selectedCardType'
          ]);

        return this;
      },


      getCode: function () {
        return 'cc';
      },

      getIcons: function (type) {
        return window.checkoutConfig.payment.ccform.icons.hasOwnProperty(type) ?
          window.checkoutConfig.payment.ccform.icons[type]
          : false;
      },

      getData: function () {
        return {
          'method': this.item.method,
          'additional_data': {
            'cc_cid': this.creditCardVerificationNumber(),
            'cc_ss_start_month': this.creditCardSsStartMonth(),
            'cc_ss_start_year': this.creditCardSsStartYear(),
            'cc_type': this.creditCardType(),
            'cc_exp_year': this.creditCardExpYear(),
            'cc_exp_month': this.creditCardExpMonth(),
            'cc_number': this.creditCardNumber(),
            'cc_owner_name': this.creditCardOwnerName(),
            'cc_owner_cpf': this.creditCardOwnerCpf().replace(/[^\d]+/g, ''),
            'cc_installments': this.creditCardInstallments(),
            'cc_phone': this.creditCardMobilePhone(),
          }
        };
      },

      isActive: function () {
        return true;
      },
      getCcAvailableTypes: function () {
        return window.checkoutConfig.payment.cc;
      },

      getStoreInstallments: function () {
        const cc = window.checkoutConfig.payment.cc;
        const installments = cc.installments;
        const data = Object.values(installments);
        let values = [];

        values = data;

        for (let i = 0; i < values.length; i++) {
          if (values[i].search(',')) {
            values[i] = values[i].replace(',', '.');
          }
        }
        const minParcelas = cc.min_parcela;
        const grandTotal = quote.totals().grand_total;
        let parcelas = {};

        if (grandTotal < minParcelas) {
          let calcJuros = ((grandTotal) * ((parseFloat(values[0]) + 100) / 100));
          let soma = 1;
          parcelas[`1-${calcJuros}`] = `${soma}x de R$ ${calcJuros.toLocaleString('pt-br', { style: 'currency', currency: 'BRL' })} `;
        }

        values.map((value, key) => {
          let calculo = grandTotal / (key + 1);
          if (calculo >= minParcelas) {
            let calcJuros = ((grandTotal / (key + 1)) * ((parseFloat(value) + 100) / 100));
            let soma = 1 + key;
            parcelas[`${key + 1}-${calcJuros}`] = `${soma}x de R$ ${calcJuros.toLocaleString('pt-br', { style: 'currency', currency: 'BRL' })} `;
          }
        });
        return parcelas;
      },

      getParcelas: function () {
        return _.map(this.getStoreInstallments(), function (value, key) {
          return {
            'value': key,
            'type': value
          }
        });
      },
      getCcAvailableTypesValues: function () {
        const types = window.checkoutConfig.payment.cc.cc_types.split(',');
        return types.map((value, key) => {
          return {
            'value': key,
            'type': value
          };
        })

        // return types.map(this.getCcAvailableTypes(), function (value, key) {
        //   console.log(this.getCcAvailableTypes())
        //   return {
        //     'value': key,
        //     'type': value
        //   }
        // });

      },

      getInstallments: function () {
        return window.checkoutConfig.payment.checkmo;
      },

      getListInstallments: function () {
        return _.map(this.getInstallments(), function (value, key) {
          return {
            'value': key,
            'type': value
          }
        });
      },

      getCcMonthsValues: function () {
        let years = [];

        for (let i = 0; i < 12; i++) {
          years[i] = i + 1;
        }


        return years.map((value) => {
          return {
            'value': value,
            'month': value
          }
        });
      },
      getCcYearsValues: function () {
        const data = new Date();
        let years = [];

        for (let i = 0; i < 20; i++) {
          years[i] = data.getFullYear() + i;
        }
        return _.map(years, function (value, key) {
          return {
            'value': value,
            'year': value
          }
        });
      },
    });
  }
);
