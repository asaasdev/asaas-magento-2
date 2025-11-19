define(
    [
      'Magento_Checkout/js/view/payment/default',
      'jquery',
      'Magento_Checkout/js/model/payment/additional-validators',
      'Magento_Ui/js/model/messageList'
    ],
    function (Component, $, validators, messageList) {
      'use strict';
  
      return Component.extend({
        defaults: {
          template: 'Asaas_Magento2/payment/pix',
          pixCpf: '',
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
  
          function validarCNPJ(cnpj) {
            cnpj = cnpj.replace(/[^\d]+/g, '');
  
            if (cnpj == '') return false;
  
            if (cnpj.length != 14)
              return false;
  
            // Elimina CNPJs invalidos conhecidos
            if (cnpj == "00000000000000" ||
              cnpj == "11111111111111" ||
              cnpj == "22222222222222" ||
              cnpj == "33333333333333" ||
              cnpj == "44444444444444" ||
              cnpj == "55555555555555" ||
              cnpj == "66666666666666" ||
              cnpj == "77777777777777" ||
              cnpj == "88888888888888" ||
              cnpj == "99999999999999")
              return false;
  
            // Valida DVs
            console.log(cnpj)
            console.log(cnpj.length)
            var tamanho = cnpj.length - 2
            var numeros = cnpj.substring(0, tamanho);
            var digitos = cnpj.substring(tamanho);
            var soma = 0;
            var pos = tamanho - 7;
            for (let i = tamanho; i >= 1; i--) {
              soma += numeros.charAt(tamanho - i) * pos--;
              if (pos < 2)
                pos = 9;
            }
            var resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
            if (resultado != digitos.charAt(0))
              return false;
  
            tamanho = tamanho + 1;
            numeros = cnpj.substring(0, tamanho);
            soma = 0;
            pos = tamanho - 7;
            for (let i = tamanho; i >= 1; i--) {
              soma += numeros.charAt(tamanho - i) * pos--;
              if (pos < 2)
                pos = 9;
            }
            resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
            if (resultado != digitos.charAt(1))
              return false;
  
            return true;
  
          }
  
          isValid = this.pixCpf().length <= 14 ? validarCPF(this.pixCpf()) : validarCNPJ(this.pixCpf());
          if (!isValid) {
            messageList.addErrorMessage({
              message: "CPF/CNPJ inválido"
            });
          }
  
          var $form = $('#' + this.getCode() + '-form');
          return $form.validation() && $form.validation('isValid') && isValid;
        },
  
        getMailingAddress: function () {
          return window.checkoutConfig.payment.checkmo.mailingAddress;
        },
  
        getInstructions: function () {
          return window.checkoutConfig.payment.instructions[this.item.method];
        },
  
  
        getCpfCnpjValue: function () {
          const cpfcnpj = window.checkoutConfig.payment.cc.cpf_cnpj;
          if(!cpfcnpj){
            this.pixCpf('');
          }
          else{
            this.pixCpf(cpfcnpj);
          }
        },
  
        initObservable: function () {
          this._super()
            .observe([
              'pixCpf'
            ]);
  
          return this;
        },
        initialize: function () {
          var self = this;
          // setTimeout(function(){
  
          // const input_cpf = document.getElementsByClassName("cpf")[0];
  
          // input_cpf.addEventListener("focus" , function(event) {
          //     input_cpf.value = "___.___.___-__"
          //     setTimeout(function() {
          //         input_cpf.setSelectionRange(0, 0)
          //     }, 1)
          // })
  
          // input_cpf.addEventListener("blur" , function() {
          //     this.value = ""
          // })
  
          // input_cpf.addEventListener("keydown", function(event) {
          //     event.preventDefault()
          //     if("0123456789".indexOf(event.key) !== -1
          //         && this.value.indexOf("_") !== -1) {
          //             this.value = this.value.replace(/_/, event.key)
          //             const next_index = this.value.indexOf("_")
          //             this.setSelectionRange(next_index, next_index)
          //     } else if (event.key === "Backspace") {
          //         this.value = this.value.replace(/(\d$)|(\d(?=\D+$))/, "_")
          //         const next_index = this.value.indexOf("_")
          //         this.setSelectionRange(next_index, next_index)
          //     }
          // }) }, 5000);
          this._super();
  
          //Set expiration year to credit card data object
          this.pixCpf.subscribe(function (value) {
            boleto.pixCpf = value;
          });
        },
  
  
        getData: function () {
          return {
            'method': this.item.method,
            'additional_data': {
              'pix_cpf': this.pixCpf().replace(/[^\d]+/g, '')
            }
          };
        },
        getCode: function () {
          return 'pix';
        }
      }
      );
    }
  );