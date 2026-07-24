---
layout: home

hero:
  name: Maybe
  text: PHP without null. Errors without try/catch.
  tagline: Option, Result, Schema, DTO and Async — typed success and error paths for PHP 7.4+, Windows and legacy codebases.
  actions:
    - theme: brand
      text: Get Started
      link: /guide/getting-started
    - theme: alt
      text: View on GitHub
      link: https://github.com/gabrielalmir/maybe

features:
  - icon:
      src: /icons/option.svg
    title: Option
    details: Model optional values without null checks scattered everywhere. Some/None with map, flatMap, filter and safe unwrapping.
  - icon:
      src: /icons/result.svg
    title: Result
    details: Typed success/error flows without exceptions as control flow. Chain fallible operations with andThen and recover with orElse.
  - icon:
      src: /icons/schema.svg
    title: Schema
    details: Zod-inspired, immutable parsing and validation. Compose strings, ints, enums, arrays and object shapes with rich error reporting.
  - icon:
      src: /icons/dto.svg
    title: DTO
    details: Validated object mapping from raw input. One schema, one immutable DTO — returns a Result instead of throwing.
  - icon:
      src: /icons/async.svg
    title: Async
    details: Concurrent execution via child processes (proc_open). No extensions required — works on Windows and shared hosting.
  - icon:
      src: /icons/ci3.svg
    title: CodeIgniter 3 ready
    details: First-class helpers and global aliases for legacy CI3 apps. Adopt incrementally, one controller at a time.
---

## Start where errors already hurt

No framework coupling, no required extensions, no rewrite. Install it and adopt `Schema`, `DTO` and `Result` one boundary at a time.

```bash
composer require gabrielalmir/maybe
```

New here? Read [**Why Maybe?**](/guide/why-maybe) for the case against `null` and exceptions, follow the [**Tutorial**](/guide/tutorial) to build a validated flow end to end, or keep the [**API Reference**](/guide/api-reference) open while you work.
