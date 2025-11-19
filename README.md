# Módulo de Integração [Asaas Payments](https://www.asaas.com/)

## Instalação

> Recomendamos ter um ambiente de teste para validar alterações e atualizações antes de atualizar sua loja de produção. Além disso, certifique-se de criar um backup com todas as informações antes de executar qualquer procedimento de atualização/instalação.

### Versões Compatíveis

- [x] 2.4.x

### Pré-requisitos

- A versão do PHP deve ser pelo menos 7.4.X.

### Instalação do Módulo Asaas

- Baixe o módulo clonando este repositório e siga os passos abaixo de acordo com a forma como sua loja foi instalada:

### Instalar usando Composer

1. Instale via packagist
   - ```composer require asaas/module-magento2```
       - Nesse momento, suas credenciais de autenticação do Magento podem ser solicitadas. Se você tiver alguma dúvida, há uma descrição de como proceder neste [link da documentação oficial](http://devdocs.magento.com/guides/v2.0/install-gde/prereq/connect-auth.html).
2. Execute os comandos:
   - ```php bin/magento setup:upgrade```
   - ```php bin/magento setup:static-content:deploy``` ou ```php bin/magento setup:static-content:deploy pt_BR```, de acordo com as configurações da sua loja.

### Instalar usando GitHub

- Se sua loja foi criada através do clone ou download do projeto Magento, siga estes passos:

1. Extraia o conteúdo do download ZIP e mova-o para a pasta ```Asaas/Magento2/```.
2. Verifique se os diretórios na sua loja estão assim: `app/code/Asaas/Magento2`.
3. Execute o comando ```bin/magento setup:upgrade```
4. Execute o comando ```bin/magento setup:di:compile```
5. Execute o comando ```bin/magento setup:static-content:deploy -f```
6. Execute o comando ```bin/magento cache:clean```

### Configurações

Acesse o Painel Administrativo do Magento e, através do menu à esquerda, vá para `Stores` > `Configuration` > `Customers` > `Customer Configuration` > `Name and Address Options`. Em `Number of Lines in a Street Address` você deve informar o número 4, conforme mostrado na imagem abaixo:

![FOTO 1](.github/img/01.png)

Após configurar o Cliente, acesse o Painel Administrativo do Magento e, através do menu à esquerda, vá para `Stores` > `Configuration` > `Sales` > `Payment Methods`. A tela para configurar os métodos de pagamento da loja será carregada.

<p align="center">
  <img src=".github/img/02.png" />
</p>

### Como habilitar o Asaas no seu site

No primeiro bloco de informações, há a configuração para habilitar ou desabilitar o módulo completamente, marque `Yes` para continuar a configuração.

Em seguida, temos `General Settings`, `Credit Card Settings`, `Billet Settings` e `Pix Settings`.

Nota: Para que as configurações a seguir funcionem, todos os passos anteriores devem ter sido seguidos.

![FOTO 3](.github/img/03.png)

### Configurações Gerais

- Api Key
	- Chave de integração da conta Asaas. Tokens de produção e sandbox são distintos.

- Debug
    - Habilita ou desabilita a funcionalidade de debug.

- Environment
	- Seleciona qual versão do ambiente o site estará apontando. Os ambientes disponíveis são: Desenvolvimento e Produção.

- URL para Webhooks de Cobrança
	- URL a ser informada no webhook de cobrança no site do Asaas, para que no momento da aprovação do pagamento o status do pedido seja alterado.

- Display Order
    - Ordem de exibição dos métodos de pagamento habilitados no módulo sendo mostrados na tela de Checkout.

- Authentication Token
    - Token para autenticar solicitações provenientes do Webhook do Asaas.

- Habilitar notificações entre Asaas e comprador
    - Habilita mensagens de email informando alterações no status do pagamento. Esta opção pode ser habilitada ou não.
- Criar Fatura para o pedido se o pagamento for recebido:
  - Gera a fatura do pedido quando recebe a notificação de pagamento aprovado.
![FOTO 4](.github/img/04.png)

### Configurações de Cartão de Crédito

- Enabled
	- Habilita ou desabilita o método de pagamento por cartão de crédito.

- Parcelamento
    - Define o número máximo de parcelas permitidas e a porcentagem de juros para cada parcela.

- Bandeiras Disponíveis
	- Seleciona as bandeiras de cartão de crédito suportadas pela loja para pagamento.

- Valor mínimo da parcela
    - Define o valor mínimo da parcela permitido.

![FOTO 5](.github/img/05.png)

### Configurações de Boleto

- Enabled
	- Habilita ou desabilita o método de pagamento por boleto.

- Dias de validade do boleto
    - Obtém a data atual e adiciona o número de dias solicitados para a expiração do boleto.

- Mensagem para o usuário
	- Mensagem exibida na tela de agradecimento após a conclusão do pedido.

- Configurações de Desconto, Configurações de Juros e Configurações de Multa permitem definir os descontos, juros e multas do boleto, respectivamente.

![FOTO 6](.github/img/06.png)

### Configurações de Pix

- Enabled
	- Habilita ou desabilita o método de pagamento por Pix.

- Dias de validade do Pix
    - Obtém a data atual e adiciona o número de dias solicitados para a expiração do QR Code.

- Mensagem para o usuário
	- Mensagem exibida na tela de agradecimento após a conclusão do pedido.

![FOTO 7](.github/img/07.png)