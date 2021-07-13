# Módulo de Nota Fiscal para WHMCS via NFE.io

Automatize a emissão de notas fiscais no WHMCS com a [NFE.io](https://nfe.io "NFE.io")!

A NFE.io é um sistema de emissão de notas fiscais que automatiza a comunicação com as prefeituras. Com a NFE.io, você se livra de diversas tarefas tediosas, melhorando o desempenho do seu negócio. E melhor, você economiza tempo e dinheiro.
## Telas do módulo

### Tela de configurações

Contém os campos para a inserção dos parâmetros necessários para o funcionamento do módulo e outras opções que podem ser personalizadas.

[![](http://whmcs.linknacional.com.br/prints/img1.png)](http://whmcs.linknacional.com.br/prints/img1.png)

### Listagem de notas fiscais
O módulo conta com uma listagem de notas fiscais, para acessar a ferramenta, dentro do admin do WHMCS no menu superior passe o mouse na opção "Addons" e clique na opção: NFE.io, irá visualizar uma listagem da situação das notas fiscais.

Caso a opção não esteja disponível no menu, verifique as configurações do módulo na opção "Controle de Acesso" e certifique-se de que opção "Full administrator" esteja marcada.

[![Listagem de notas fiscais](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2020/05/nfe_list_screenshot.png "Listagem de notas fiscais")](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2020/05/nfe_list_screenshot.png "Listagem de notas fiscais")

### Configurações de Códigos de serviços
Dentro da listagem de nota fiscal, possui a opção de listar e cadastrar os códigos de serviços. Se algum dos serviços ofertados possuir código de serviço diferente do definido nas configurações, esse é o local para definição do código do serviço individualmente.

[![Listagem de notas fiscais](http://whmcs.linknacional.com.br/prints/img2.png "Listagem de notas fiscais")](http://whmcs.linknacional.com.br/prints/img2.png "Listagem de notas fiscais")

### Visualização de Fatura via admin
Na área de visualização de uma fatura, é possível gerenciar a nota fiscal manualmente.

[![Ações na edição da fatura](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2020/05/nfe_invoice_screenshot.png "Ações na edição da fatura")](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2020/05/nfe_invoice_screenshot.png "Ações na edição da fatura")

## Principais funcionalidades

✓ Emite notas fiscais manualmente.

✓ Emite notas fiscais automaticamente, quando a fatura é gerada, ou quando a fatura é paga.

✓ Permite agendar a emissão de notas fiscais depois um determinado número de dias após da confirmação dos pagamentos.

✓ Emite notas fiscais de forma sequencial, evitando sobrecargas nos sites das prefeituras.

✓ Exibe o status da NFE e adiciona botões de ações relacionadas às notas na página de edição das faturas.

✓ Cancela a nota fiscal quando a fatura é cancelada (opcional).

✓ Exibe nas configurações do módulo quando há uma versão mais recente disponível para download.

✓ Opcionalmente, salva o debug das chamadas à API NFE.io no log de Módulo do WHMCS para diagnóstico e aprendizado.

✓ Opcionalmente, seleciona nas configurações do módulo a opção de enviar o número inscrição municipal para a nota fiscal.

✓ Opcionalmente, seleciona nas configurações do módulo a opção de enviar a nota fiscal por e-mail automaticamente.

## Requisitos do sistema

- WHMCS versão 7.2.1 ou superior;
- PHP 5.6 ou superior
- Tarefas cron do WHMCS devem estar funcionando a cada 5 minutos, conforme descrito na documentação oficial (https://docs.whmcs.com/Crons);
- É necessário um portal de pagamento ativado e que a criação de faturas do WHMCS esteja funcional, sendo que as notas fiscais são emitidas no momento da criação ou após o pagamento das faturas geradas manual ou automaticamente pelo WHMCS.

## Instalação

1. Faça download do módulo [neste link](https://github.com/nfe/whmcs-addon/archive/master.zip "neste link");
2. Descompacte o arquivo .zip;
3. Copie o diretório `/nfeio/`, localizados na pasta `/modules/addons/` do arquivo recém descompactado, para a pasta `/modules/addons/` da instalação do WHMCS;

### Pré configuração

No painel administrativo do WHMCS, crie um campo personalizado de cliente para CPF e/ou CNPJ. Caso prefira, você pode criar dois campos distintos, sendo um campo apenas para CPF e outro campo apenas para CNPJ. O módulo identifica os campos do perfil do cliente automaticamente.

### Configuração

Após instalar entre no Admin do WHMCS e acesse as configurações. Dentro das opções de configurações pesquise por: "Módulos Addon" (www.seudominio.com/admin/configaddonmods.php). Procure pelo módulo NFE.io e clique no botão "Ativar"

Após a ativação do módulo, o botão "Configurar" ficará disponível, clique no botão para acessar as configurações do módulo.

Para informações detalhada de como configurar cada campo veja no tópico [Configurações do módulo](https://github.com/LinkNacional/whmcs-addon#configura%C3%A7%C3%B5es-do-m%C3%B3dulo "Configurações do módulo").

## Atualização

1. Faça download da última versão do módulo [aqui](https://github.com/nfe/whmcs-addon/archive/master.zip "Baixar última versão do módulo");
2. Descompacte o arquivo .zip;
3. Dentro da instalação do seu WHMCS remova a pasta `/modules/addons/nfeio/`;
4. Copie o diretório `/nfeio/`, localizados na pasta `/modules/addons/` do arquivo recém descompactado, para a pasta `/modules/addons/` da instalação do WHMCS;

## Configurações do módulo

1. **API Key**: (Obrigatório) Chave de acesso privada gerado na sua conta NFE.io, necessária para a autenticação das chamadas à API (Obter Api Key);
2. **ID da Empresa**: (Obrigatório) Nesse campo você deve indicar o ID da empresa ao qual serão associadas as notas fiscais geradas pelo WHMCS. (Obter ID da empresa);
3. **Código de Serviço Principal**: (Obrigatório) O código de serviço varia de acordo com a categoria de tributação do negócio. Saiba mais sobre o código de serviço aqui;
4. **Agendar Emissão**: Número de dias após o pagamento da fatura que as notas devem ser emitidas. Preencher essa opção desativa a opção anterior;
5. **Quando emitir NFE**: Selecione se deseja que as notas fiscais sejam geradas quando a fatura é publicada ou quando a fatura é paga;
6. **Cancelar NFE**: Se essa opção está ativada, o módulo cancela a nota fiscal quando a fatura cancelada;
7. **Debug**: Marque essa opção para salvar informações de diagnóstico no Log de Módulo do WHMCS;
8. **Inscrição Municipal**, **CPF**, **CNPJ**: Marque o campo personalizado definido para ser a Inscrição Municipal.
9. **Aplicar imposto automaticamente em todos os produtos**: Esta opção define que todos os serviços terão impostos aplicados, caso contrário a aplicação de imposto é selecionada de forma individual por serviço.
10. **O que deve aparecer nos detalhes da fatura?**: Define o que vai aparecer nos detalhes das notas fiscais emitidas.
11. **Controle de Acesso**: Escolha os grupos de administradores ou operadores que terão permissão para acessar a lista de faturas gerada pelo módulo no menu Addons > Gofas NFE.io.

## Configurações dos produtos e serviços

Os produtos podem ter configurações de código de serviço individuais:

Em Addons>NFE.io>código dos Produtos é possivel configurar um código de serviço para cada produto/serviço cadastrado.

**_o código individual vai ter prioridade sobre o definido nas configurações do módulo._**

E também há configurações de aplicação do imposto:

Nas configurações do módulo como foi explicado anteriormente, há a opção de aplicar imposto automaticamente em todos os produtos, onde se marcados sim, todos os produtos/serviços cadastrados vão ser marcados para aplicar os impostos.

se desejar fazer essas configurações individualmente pode entrar em configurações>Produtos/Serviços e escolher o produto para configurar e marcar a caixa Aplicar Imposto.

## Link para download da nota em PDF ou XML

Para inserir um link na fatura para o download da nota fiscal em PDF ou em XML, edite o arquivo viewinvoice.tpl da pasta template do WHMCS, e cole o código abaixo, logo abaixo da linha 17 do arquivo:

```php
{if $status eq "Paid" || $clientsdetails.userid eq "6429"}<i class="fal fa-file-invoice" aria-hidden="true"></i> NOTA FISCAL <a href="/modules/addons/nfeio/create_doc.php?type=pdf&invoice_id={$invoiceid}" target="_blank" class="btn btn-link" tite="Nota Fiscal disponível 24 horas após confirmação de pagamento.">PDF</a> | <a href="/modules/addons/nfeio/create_doc.php?type=xml&invoice_id={$invoiceid}" target="_blank" class="btn btn-link" tite="Nota Fiscal disponível 24 horas após confirmação de pagamento.">XML</a>{/if}
```

## Emissão personalizada de notas para cliente

Para inserir uma opção personalizada de quando é emitido a NFE para cada cliente crie um campo personalizado em `Configurações > Campos Personalizados dos Clientes` com o nome `Emitir Nota Fiscal`,Tipos de campo `Lista de Opção` e em Selecionar Opções `nenhum (padrão do WHMCS) deve seguir a configuração do modulo.,Quando a Fatura é Gerada,Quando a Fatura é Paga`,como no exemplo:
[![](http://whmcs.linknacional.com.br/prints/campo_personalizado.png)](http://whmcs.linknacional.com.br/prints/campo_personalizado.png)

## Changelog

#### IMPORTANTE: Ao atualizar, após substituir os arquivos pelos mais recentes, acesse as configurações do módulo no menu `Opções > Módulos Addon > Gofas NFE.io` do painel administrativo do WHMCS e clique em "Salvar Alterações". Isso garente que os novos parâmetros serão gravados corretamente no banco de dados.

### IMPORTANTE: na versão 2.0.0 seguir os passos do tópico [Link para download da nota em PDF ou XML](https://github.com/nfe/whmcs-addon#link-para-download-da-nota-em-PDF-ou-XML "");

### v2.0.0
- Melhorias no link para download da nota em PDF ou XML
- Alterações nas nomeclaturas do código
- Melhorias nas funcionalidades de custom fields
- Adicionado campo personalizado de descrição por serviço/produto.

### v1.4.0

- Migração da tratativa do RPS para a NFe realizada
- Melhorias na segurança dos arquivos
- Melhorias na qualidade do código

### v1.3.3

- Ajuste na descrição da nota fiscal.

### v1.3.2

- Ajuste para correção da emissão automática de notas quando pagas.

### v1.3.1

- ajuste para correção de retorno de callback.

### v1.3.0

- link para relatório do sistema legado
- botão para cancelar nota fiscal
- log, data e hora da emissão do log
- verificação de conexão com nfe
- verificação automática de campo RPS
- verificação de campo personalizado
- campo personalizado no cliente para emissão da nota

### v1.2.10

- correção enviar endereço de e-mail na nota

### v1.2.9

- criação de arquivo de debug
- verificação do retorno CEP
- validação de versão do modulo via github
- impedir emissão duplicada de nota fiscal de fatura

### v1.2.7

- envio do nome da empresa ao invés do nome pessoa física quando o CNPJ estiver definido
- criar nota fiscal de acordo com o código de serviço de cada serviço
- corrigido erro de caracteres especiais
- opção de criar nota individualmente por tipo de serviço
- emissão de nota fiscal a partir da data de instalação do módulo
- opção de descrição do serviço na nota: referente a fatura ou nome do serviço.
- ajuste de link das notas fiscais na fatura para abrir todas as notas.
- ajuste de instalação do módulo

### v1.2.6

- opção manual para criação de notas fiscais.

### v1.2.5

- criação de link na fatura para o XML da nota fiscal.

### v1.2.4

- Nova opção de configuração no disparo de nota fiscal automatica por e-mail.
- Ajustes com informações e links de suporte.

### v1.2.3

- Ajustes Garante que a nota não sera duplicada, criação de link da nota fiscal, opção de inscrição municipal.

### v1.2.2

- Garante que o rpsSeraiNumber não seja alterado quando já configurado manualmente.

#### v1.2.1

- Corrigido erro que alterava a série do RPS nas configurações de acordo com a série RPS das NFEs já geradas.

#### v1.2.0

- Novo campo nas configurações para informar a Série do RPS (RPS Serial Number). Será preenchido automaticamente na próxima emissão, caso não preenchido;
- Novo campo nas configurações para informar o número RPS (RPS Number). Caso não preenchido, será preenchido automaticamente na próxima emissão, após consultar a NFE mais recente gerada. Não sendo gerado ou configurado nenhum número RPS, o módulo irá configurar automaticamente com "1" o valor desse campo;

#### v1.1.3

- Agora o número RPS é obtido consultando a NFE mais recente gerada;

#### v1.1.2

- Melhoria na verificação de atualizações;

#### v1.1.1

- Obtém via API o rpsSerialNumber e rpsNumber da empresa antes de gerar cada nota fiscal;
- O rpsNumber da nova NFE a ser gerada sempre é "último rpsNumber + 1".

#### v1.0.1

- Corrigido bug ao salvar NFE no banco de dados na criação da fatura.

#### v1.0.0

- Lançamento.

© 2021 [Manutenção Link Nacional](https://www.linknacional.com.br/suporte-whmcs)
