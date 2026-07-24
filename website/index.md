---
layout: home

hero:
  name: Maybe
  text: Explicit, predictable business logic for PHP
  tagline: Option, Result, Schema, DTO and Async — Rust-inspired primitives that work on PHP 7.4+, Windows and legacy codebases.
  actions:
    - theme: brand
      text: Get Started
      link: /guide/getting-started
    - theme: alt
      text: View on GitHub
      link: https://github.com/gabrielalmir/maybe

features:
  - icon: 🎁
    title: Option
    details: Model optional values without null checks scattered everywhere. Some/None with map, flatMap, filter and safe unwrapping.
  - icon: ✅
    title: Result
    details: Typed success/error flows without exceptions as control flow. Chain fallible operations with andThen and recover with orElse.
  - icon: 🧪
    title: Schema
    details: Zod-inspired, immutable parsing and validation. Compose strings, ints, enums, arrays and object shapes with rich error reporting.
  - icon: 📦
    title: DTO
    details: Validated object mapping from raw input. One schema, one immutable DTO — returns a Result instead of throwing.
  - icon: ⚡
    title: Async
    details: Concurrent execution via child processes (proc_open). No extensions required — works on Windows and shared hosting.
  - icon: 🔥
    title: CodeIgniter 3 ready
    details: First-class helpers and global aliases for legacy CI3 apps. Adopt incrementally, one controller at a time.
---

## Errors as values, not surprises

```php
use Maybe\Result\Result;

$response = loadUser($id)
    ->andThen(fn (array $user): Result => chargeSubscription($user))
    ->map(fn (array $invoice): string => $invoice['number'])
    ->match(
        fn (string $number): string => "Invoice {$number} created",
        fn (string $error): string => "Failed: {$error}"
    );
```

Install it with Composer and start with `Schema`, `DTO` and `Result` — no framework coupling, no extensions, no rewrite.

```bash
composer require gabrielalmir/maybe
```
