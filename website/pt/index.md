---
layout: home

hero:
  name: Maybe
  text: Lógica de negócio explícita e previsível para PHP
  tagline: Option, Result, Schema, DTO e Async — primitivas inspiradas em Rust que funcionam em PHP 7.4+, Windows e bases de código legadas.
  actions:
    - theme: brand
      text: Começar
      link: /pt/guide/getting-started
    - theme: alt
      text: Ver no GitHub
      link: https://github.com/gabrielalmir/maybe

features:
  - icon: 🎁
    title: Option
    details: Modele valores opcionais sem espalhar checagens de null. Some/None com map, flatMap, filter e unwrap seguro.
  - icon: ✅
    title: Result
    details: Fluxos de sucesso/erro tipados sem exceções como controle de fluxo. Encadeie operações falíveis com andThen e recupere com orElse.
  - icon: 🧪
    title: Schema
    details: Parsing e validação imutáveis inspirados no Zod. Componha strings, ints, enums, arrays e shapes de objetos com erros detalhados.
  - icon: 📦
    title: DTO
    details: Mapeamento validado de objetos a partir de input bruto. Um schema, um DTO imutável — retorna Result em vez de lançar exceção.
  - icon: ⚡
    title: Async
    details: Execução concorrente via processos filhos (proc_open). Sem extensões — funciona em Windows e hospedagem compartilhada.
  - icon: 🔥
    title: Pronto para CodeIgniter 3
    details: Helpers e aliases globais para apps CI3 legados. Adote incrementalmente, um controller por vez.
---

## Erros como valores, não surpresas

```php
use Maybe\Result\Result;

$response = loadUser($id)
    ->andThen(fn (array $user): Result => chargeSubscription($user))
    ->map(fn (array $invoice): string => $invoice['number'])
    ->match(
        fn (string $number): string => "Fatura {$number} criada",
        fn (string $error): string => "Falhou: {$error}"
    );
```

Instale com Composer e comece por `Schema`, `DTO` e `Result` — sem acoplamento a framework, sem extensões, sem reescrita.

```bash
composer require gabrielalmir/maybe
```
