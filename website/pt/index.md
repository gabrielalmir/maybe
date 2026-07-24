---
layout: home

hero:
  name: Maybe
  text: Torne ausência e falha explícitas.
  tagline: Option, Result, Schema e DTO para aplicações PHP 7.4+ com poucas dependências. Adote uma fronteira por vez em Windows e bases legadas.
  actions:
    - theme: brand
      text: Comece em 5 minutos
      link: /pt/guide/getting-started
    - theme: alt
      text: Ver uma migração real
      link: /pt/guide/migration

features:
  - icon:
      src: /icons/option.svg
    title: Option
    details: Modele valores opcionais sem espalhar checagens de null. Some/None com map, flatMap, filter e unwrap seguro.
    link: /pt/guide/option
    linkText: Ler Option
  - icon:
      src: /icons/result.svg
    title: Result
    details: Fluxos de sucesso/erro tipados sem exceções como controle de fluxo. Encadeie operações falíveis com andThen e recupere com orElse.
    link: /pt/guide/result
    linkText: Ler Result
  - icon:
      src: /icons/schema.svg
    title: Schema
    details: Parsing e validação imutáveis inspirados no Zod. Componha strings, ints, enums, arrays e shapes de objetos com erros detalhados.
    link: /pt/guide/schema
    linkText: Ler Schema
  - icon:
      src: /icons/dto.svg
    title: DTO
    details: Mapeamento validado de objetos a partir de input bruto. Um schema, um DTO imutável — retorna Result em vez de lançar exceção.
    link: /pt/guide/dto
    linkText: Ler DTO
---

## Comece na fronteira que você já tem

Sem acoplamento a framework e sem reescrita. Valide o input, modele uma operação falível e decida o resultado na borda.

<div class="maybe-install-card">
  <div>
    <p>Instale os primitives centrais</p>
    <code>composer require gabrielalmir/maybe</code>
  </div>
  <a class="VPButton medium brand" href="guide/getting-started.html">Leia os primeiros passos →</a>
</div>

## Use a fronteira que você já tem

<div class="maybe-secondary-grid">
  <a class="maybe-secondary-card" href="guide/async.html">
    <strong>Precisa de concorrência isolada?</strong>
    <span>Execute trabalho serializável em processos filhos com Async, incluindo timeout, pool e cancelamento.</span>
  </a>
  <a class="maybe-secondary-card" href="guide/codeigniter-3.html">
    <strong>Mantém uma aplicação CI3?</strong>
    <span>Adote helpers e resultados explícitos, um controller por vez, sem reescrever a aplicação.</span>
  </a>
</div>

Novato por aqui? Leia [**Por que Maybe?**](/pt/guide/why-maybe) para entender os trade-offs, siga o [**Tutorial**](/pt/guide/tutorial) para construir um fluxo validado de ponta a ponta, ou mantenha a [**Referência de API**](/pt/guide/api-reference) aberta enquanto trabalha.
