---
layout: home

hero:
  name: Maybe
  text: PHP sem null. Erros sem try/catch.
  tagline: Option, Result, Schema, DTO e Async — caminhos de sucesso e erro tipados para PHP 7.4+, Windows e bases de código legadas.
  actions:
    - theme: brand
      text: Começar
      link: /pt/guide/getting-started
    - theme: alt
      text: Ver no GitHub
      link: https://github.com/gabrielalmir/maybe

features:
  - icon:
      src: /icons/option.svg
    title: Option
    details: Modele valores opcionais sem espalhar checagens de null. Some/None com map, flatMap, filter e unwrap seguro.
  - icon:
      src: /icons/result.svg
    title: Result
    details: Fluxos de sucesso/erro tipados sem exceções como controle de fluxo. Encadeie operações falíveis com andThen e recupere com orElse.
  - icon:
      src: /icons/schema.svg
    title: Schema
    details: Parsing e validação imutáveis inspirados no Zod. Componha strings, ints, enums, arrays e shapes de objetos com erros detalhados.
  - icon:
      src: /icons/dto.svg
    title: DTO
    details: Mapeamento validado de objetos a partir de input bruto. Um schema, um DTO imutável — retorna Result em vez de lançar exceção.
  - icon:
      src: /icons/async.svg
    title: Async
    details: Execução concorrente via processos filhos (proc_open). Sem extensões — funciona em Windows e hospedagem compartilhada.
  - icon:
      src: /icons/ci3.svg
    title: Pronto para CodeIgniter 3
    details: Helpers e aliases globais para apps CI3 legados. Adote incrementalmente, um controller por vez.
---

## Comece onde os erros já doem

Sem acoplamento a framework, sem extensões obrigatórias, sem reescrita. Instale e adote `Schema`, `DTO` e `Result` uma fronteira por vez.

```bash
composer require gabrielalmir/maybe
```

Leia o [guia de Primeiros Passos](/pt/guide/getting-started) ou vá direto para [Result](/pt/guide/result) se tratamento de erros foi o que te trouxe até aqui.
