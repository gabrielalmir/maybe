---
layout: home

hero:
  name: maybe
  text: PHP sem null.<br>Erros sem try/catch.
  tagline: Option, Result, Schema e DTO para PHP 7.4+. Adote uma fronteira por vez, sem reescrita.
  actions:
    - theme: brand
      text: Comece em 5 minutos
      link: /pt/guide/getting-started.html
    - theme: alt
      text: Ver uma migração real
      link: /pt/guide/migration.html

features:
  - icon:
      light: /icons/option.svg
      dark: /icons/option-dark.svg
      alt: ''
      width: '24'
      height: '24'
      wrap: true
    title: Option
    details: Modele valores opcionais sem espalhar checagens de null. Some/None com map, flatMap, filter e unwrap seguro.
    link: /pt/guide/option.html
    linkText: Ler Option
  - icon:
      light: /icons/result.svg
      dark: /icons/result-dark.svg
      alt: ''
      width: '24'
      height: '24'
      wrap: true
    title: Result
    details: Fluxos de sucesso/erro tipados sem exceções como controle de fluxo. Encadeie operações falíveis com andThen e recupere com orElse.
    link: /pt/guide/result.html
    linkText: Ler Result
  - icon:
      light: /icons/schema.svg
      dark: /icons/schema-dark.svg
      alt: ''
      width: '24'
      height: '24'
      wrap: true
    title: Schema
    details: Parsing e validação imutáveis inspirados no Zod. Componha strings, ints, enums, arrays e shapes de objetos com erros detalhados.
    link: /pt/guide/schema.html
    linkText: Ler Schema
  - icon:
      light: /icons/dto.svg
      dark: /icons/dto-dark.svg
      alt: ''
      width: '24'
      height: '24'
      wrap: true
    title: DTO
    details: Mapeamento validado de objetos a partir de input bruto. Um schema, um DTO imutável. Retorna Result em vez de lançar exceção.
    link: /pt/guide/dto.html
    linkText: Ler DTO
  - icon:
      light: /icons/async.svg
      dark: /icons/async-dark.svg
      alt: ''
      width: '24'
      height: '24'
      wrap: true
    title: Async
    details: Execute trabalho serializável em processos filhos isolados. Timeout, pool e cancelamento, sem adicionar dependência de runtime.
    link: /pt/guide/async.html
    linkText: Ler Async
  - icon:
      light: /icons/ci3.svg
      dark: /icons/ci3-dark.svg
      alt: ''
      width: '24'
      height: '24'
      wrap: true
    title: CodeIgniter 3
    details: Adote resultados explícitos um controller por vez. Helpers globais para bases legadas, sem reescrita.
    link: /pt/guide/codeigniter-3.html
    linkText: Ler o guia CI3

proof:
  items:
    - value: '1'
      label: dependência de runtime (opis/closure)
    - value: 7.4 → 8.5
      label: versões de PHP testadas na CI
    - value: Windows
      label: job Async roda na CI, sem pcntl
    - value: MIT
      label: permissiva, sem contrapartidas

beforeAfter:
  title: Mesmo caminho de código. Só um deles conta o que aconteceu.
  lead: Um e-mail de confirmação de pedido falha. Ou o checkout inteiro quebra por causa de um efeito colateral não crítico, ou a falha é engolida e ninguém descobre que o cliente nunca foi avisado.
  legacyLabel: Sem Maybe
  legacyNote: Quem chamou não tem como saber se o cliente foi avisado, nem como distinguir um endereço malformado de um relay que deu timeout.
  maybeLabel: Com Maybe
  note: 'O payload de erro mantém `retryable` explícito. Um endereço malformado e um relay SMTP instável são problemas diferentes: um pede correção de dado, o outro uma fila de retry. O tipo impede que sejam tratados igual por acidente.'

composition:
  title: Cinco blocos, uma filosofia.
  lead: Os blocos foram desenhados para compor entre si, em uma única dependência que instala no PHP 7.4.
  edges:
    - from: Schema
      to: Result
      detail: safeParse() nunca lança. Devolve um Result carregando um ValidationErrorBag.
    - from: DTO
      to: Schema
      detail: Um schema, um objeto tipado e imutável, construído a partir de input bruto.
    - from: Option
      to: Result
      detail: okOr() transforma um valor ausente em um erro nomeado na fronteira.

legacy:
  eyebrow: O nicho
  title: Roda onde o PHP legado realmente vive.
  lead: Não é reescrita, não é framework. Uma dependência pequena que instala no PHP que você já tem.
  points:
    - title: PHP 7.4 para cima
      detail: Sem enums, sem promotion, sem readonly. Testado na CI contra 7.4, 8.2, 8.3, 8.4 e 8.5.
    - title: Windows, sem extensões
      detail: Async usa proc_open, não pcntl. Um job de CI dedicado prova isso no Windows.
    - title: CodeIgniter 3
      detail: Helpers globais e uma library carregável. Adote um controller por vez.
    - title: Honesto sobre async
      detail: Concorrência por processos, deliberadamente não um event loop. Precisa de um? Use amphp ou reactphp.

caseStudies:
  eyebrow: Em produção
  title: Três fronteiras que valem nomear.
  items:
    - title: E-mail transacional que não pode derrubar o checkout
      risk: Um e-mail de confirmação falha. Ou o checkout inteiro quebra por um efeito colateral não crítico, ou a falha é silenciosamente engolida.
      link: /pt/guide/case-studies.html
      linkText: Ler o estudo de caso
      example: https://github.com/gabrielalmir/maybe/blob/main/examples/scenario-transactional-email.php
      exampleText: Exemplo executável
    - title: Enviar pedidos ao SAP sem perder dado em silêncio
      risk: 'O SAP falha por razões estruturadas: documento duplicado, centro de custo ausente, sessão expirada, timeout. Código legado colapsa tudo na mesma não-resposta.'
      link: /pt/guide/case-studies.html
      linkText: Ler o estudo de caso
      example: https://github.com/gabrielalmir/maybe/blob/main/examples/scenario-sap-order-integration.php
      exampleText: Exemplo executável
    - title: Validação de contratos com regras entre campos
      risk: Validação espalhada por um controller deixa um contrato ser salvo pela metade em estado inválido, com erros desestruturados demais para uma tela de revisão apontar o campo culpado.
      link: /pt/guide/case-studies.html
      linkText: Ler o estudo de caso
      example: https://github.com/gabrielalmir/maybe/blob/main/examples/scenario-contract-validation.php
      exampleText: Exemplo executável

agents:
  title: Aponte seu agente para a API exata.
  lead: Um llms.txt publicado com nomes e assinaturas exatos, incluindo os getters que deliberadamente não existem, para o assistente parar de inventá-los.
  url: https://gabrielalmir.github.io/maybe/llms.txt
  note: Combinado com o AGENTS.md do repositório, para quem contribui usando um assistente.

honest:
  title: Quando não usar Maybe
  lead: 'Honestidade primeiro. Procure outra coisa quando:'
  items:
    - Você está em PHP moderno (8.1+) e já padronizou numa biblioteca especialista madura com a qual está satisfeito.
    - Você precisa de um event loop async completo com I/O não bloqueante. Use amphp ou reactphp, não o Async.
    - Você precisa de um motor de validação com catálogo grande de regras e mensagens i18n prontas.
    - Seu time prefere exceções e não vai adotar Result nas fronteiras. O valor vem do uso consistente.
  link: /pt/guide/why-maybe.html
  linkText: Ver a comparação completa

cta:
  title: Comece na fronteira que você já tem.
  lead: Sem acoplamento a framework e sem reescrita. Valide o input, modele uma operação falível e decida o resultado na borda.
  install: composer require gabrielalmir/maybe
  primary:
    text: Comece em 5 minutos
    link: /pt/guide/getting-started.html
  secondary:
    text: Ler o tutorial
    link: /pt/guide/tutorial.html
  meta: v0.4.0 · MIT · PHP ≥ 7.4
---

Novato por aqui? Leia [**Por que Maybe?**](/pt/guide/why-maybe) para entender os trade-offs, siga o [**Tutorial**](/pt/guide/tutorial) para construir um fluxo validado de ponta a ponta, ou mantenha a [**Referência de API**](/pt/guide/api-reference) aberta enquanto trabalha.
