---
layout: home

hero:
  name: Maybe
  text: Make absence and failure explicit.
  tagline: Option, Result, Schema and DTO for dependency-light PHP 7.4+ applications. Adopt one boundary at a time across Windows and legacy codebases.
  actions:
    - theme: brand
      text: Start in 5 minutes
      link: /guide/getting-started
    - theme: alt
      text: See a real migration
      link: /guide/migration

features:
  - icon:
      src: /icons/option.svg
    title: Option
    details: Model optional values without null checks scattered everywhere. Some/None with map, flatMap, filter and safe unwrapping.
    link: /guide/option
    linkText: Read Option
  - icon:
      src: /icons/result.svg
    title: Result
    details: Typed success/error flows without exceptions as control flow. Chain fallible operations with andThen and recover with orElse.
    link: /guide/result
    linkText: Read Result
  - icon:
      src: /icons/schema.svg
    title: Schema
    details: Zod-inspired, immutable parsing and validation. Compose strings, ints, enums, arrays and object shapes with rich error reporting.
    link: /guide/schema
    linkText: Read Schema
  - icon:
      src: /icons/dto.svg
    title: DTO
    details: Validated object mapping from raw input. One schema, one immutable DTO — returns a Result instead of throwing.
    link: /guide/dto
    linkText: Read DTO
---

## Start at the boundary you already have

No framework coupling, no rewrite. Validate input, model a fallible service, and decide what happens at the edge.

<div class="maybe-install-card">
  <div>
    <p>Install the core primitives</p>
    <code>composer require gabrielalmir/maybe</code>
  </div>
  <a class="VPButton medium brand" href="guide/getting-started.html">Read the first steps →</a>
</div>

## Use the boundary you have

<div class="maybe-secondary-grid">
  <a class="maybe-secondary-card" href="guide/async.html">
    <strong>Need isolated concurrency?</strong>
    <span>Run serializable work in child processes with Async, including timeouts, pools and cancellation.</span>
  </a>
  <a class="maybe-secondary-card" href="guide/codeigniter-3.html">
    <strong>Maintaining a CI3 application?</strong>
    <span>Adopt helpers and explicit outcomes one controller at a time without a rewrite.</span>
  </a>
</div>

New here? Read [**Why Maybe?**](/guide/why-maybe) for the trade-offs, follow the [**Tutorial**](/guide/tutorial) to build a validated flow end to end, or keep the [**API Reference**](/guide/api-reference) open while you work.
