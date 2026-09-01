---
layout: home

hero:
  name: maybe
  text: PHP without null.<br>Errors without try/catch.
  tagline: Option, Result, Schema and DTO for PHP 7.4+. Adopt one boundary at a time, no rewrite.
  actions:
    - theme: brand
      text: Start in 5 minutes
      link: /guide/getting-started.html
    - theme: alt
      text: See a real migration
      link: /guide/migration.html

features:
  - icon:
      light: /icons/option.svg
      dark: /icons/option-dark.svg
      alt: ''
      width: '24'
      height: '24'
      wrap: true
    title: Option
    details: Model optional values without null checks scattered everywhere. Some/None with map, flatMap, filter and safe unwrapping.
    link: /guide/option.html
    linkText: Read Option
  - icon:
      light: /icons/result.svg
      dark: /icons/result-dark.svg
      alt: ''
      width: '24'
      height: '24'
      wrap: true
    title: Result
    details: Typed success/error flows without exceptions as control flow. Chain fallible operations with andThen and recover with orElse.
    link: /guide/result.html
    linkText: Read Result
  - icon:
      light: /icons/schema.svg
      dark: /icons/schema-dark.svg
      alt: ''
      width: '24'
      height: '24'
      wrap: true
    title: Schema
    details: Zod-inspired, immutable parsing and validation. Compose strings, ints, enums, arrays and object shapes with rich error reporting.
    link: /guide/schema.html
    linkText: Read Schema
  - icon:
      light: /icons/dto.svg
      dark: /icons/dto-dark.svg
      alt: ''
      width: '24'
      height: '24'
      wrap: true
    title: DTO
    details: Validated object mapping from raw input. One schema, one immutable DTO. Returns a Result instead of throwing.
    link: /guide/dto.html
    linkText: Read DTO
  - icon:
      light: /icons/async.svg
      dark: /icons/async-dark.svg
      alt: ''
      width: '24'
      height: '24'
      wrap: true
    title: Async
    details: Run serializable work in isolated child processes. Timeouts, pools and cancellation, without adding a runtime dependency.
    link: /guide/async.html
    linkText: Read Async
  - icon:
      light: /icons/ci3.svg
      dark: /icons/ci3-dark.svg
      alt: ''
      width: '24'
      height: '24'
      wrap: true
    title: CodeIgniter 3
    details: Adopt explicit outcomes one controller at a time. Global helpers for legacy codebases, no rewrite required.
    link: /guide/codeigniter-3.html
    linkText: Read the CI3 guide
proof:
  items:
    - value: '1'
      label: runtime dependency (opis/closure)
    - value: 7.4 → 8.5
      label: PHP versions tested in CI
    - value: Windows
      label: Async job runs in CI, no pcntl
    - value: MIT
      label: permissive, no strings attached

beforeAfter:
  title: Same code path. Only one of them tells you what happened.
  lead: An order-confirmation email fails to send. Either checkout crashes over a non-critical side effect, or the failure is swallowed and nobody finds out the customer was never notified.
  legacyNote: The caller has no way to know whether the customer was notified, and no way to tell a malformed address from a timed-out relay.
  legacyLabel: Without Maybe
  maybeLabel: With Maybe
  note: 'The error payload keeps `retryable` explicit. A malformed address and a flaky SMTP relay are different problems: one needs a data fix, the other a retry queue. The type stops them being handled identically by accident.'

composition:
  title: Five blocks, one philosophy.
  lead: The blocks are designed to compose with each other, in a single dependency that installs on PHP 7.4.
  edges:
    - from: Schema
      to: Result
      detail: safeParse() never throws. It returns a Result carrying a ValidationErrorBag.
    - from: DTO
      to: Schema
      detail: One schema, one immutable typed object, built from raw input.
    - from: Option
      to: Result
      detail: okOr() turns an absent value into a named error at the boundary.

legacy:
  eyebrow: The niche
  title: It runs where legacy PHP actually lives.
  lead: Not a rewrite, not a framework. One small dependency that installs on the PHP you already have.
  points:
    - title: PHP 7.4 and up
      detail: No enums, no promotion, no readonly. Tested in CI against 7.4, 8.2, 8.3, 8.4 and 8.5.
    - title: Windows, without extensions
      detail: Async uses proc_open, not pcntl. A dedicated CI job proves it on Windows.
    - title: CodeIgniter 3
      detail: Global helpers and a loadable library. Adopt one controller at a time.
    - title: Honest about async
      detail: Process-based concurrency, deliberately not an event loop. Need one? Use amphp or reactphp.

caseStudies:
  eyebrow: In production
  title: Three boundaries worth naming.
  items:
    - title: Transactional email that can't break checkout
      risk: A confirmation email fails. Either the whole checkout crashes over a non-critical side effect, or the failure is silently swallowed.
      link: /guide/case-studies.html
      linkText: Read the case study
      example: https://github.com/gabrielalmir/maybe/blob/main/examples/scenario-transactional-email.php
      exampleText: Runnable example
    - title: Pushing orders into SAP without losing data silently
      risk: 'SAP fails for structured reasons: duplicate document, missing cost center, expired session, timeout. Legacy code collapses them all into the same non-answer.'
      link: /guide/case-studies.html
      linkText: Read the case study
      example: https://github.com/gabrielalmir/maybe/blob/main/examples/scenario-sap-order-integration.php
      exampleText: Runnable example
    - title: Contract validation with cross-field rules
      risk: Validation scattered across a controller lets a contract get half-saved in an invalid state, with errors too unstructured for a review screen to point at the offending field.
      link: /guide/case-studies.html
      linkText: Read the case study
      example: https://github.com/gabrielalmir/maybe/blob/main/examples/scenario-contract-validation.php
      exampleText: Runnable example

agents:
  title: Point your agent at the exact API.
  lead: A published llms.txt with exact method names and signatures, including the getters that deliberately do not exist, so an assistant stops inventing them.
  url: https://gabrielalmir.github.io/maybe/llms.txt
  note: Paired with AGENTS.md in the repository for anyone contributing with an assistant.

honest:
  title: When not to use Maybe
  lead: 'Honesty first. Reach for something else when:'
  items:
    - You're on modern PHP (8.1+) and already standardized on a mature specialist library you're happy with.
    - You need a full async event loop with non-blocking I/O. Use amphp or reactphp, not Async.
    - You need a validation engine with a large built-in rule catalog and i18n messages out of the box.
    - Your team strongly prefers exceptions and won't adopt Result at boundaries. The value comes from consistent use.
  link: /guide/why-maybe.html
  linkText: See the full comparison

cta:
  title: Start at the boundary you already have.
  lead: No framework coupling, no rewrite. Validate input, model a fallible service, and decide what happens at the edge.
  install: composer require gabrielalmir/maybe
  primary:
    text: Start in 5 minutes
    link: /guide/getting-started.html
  secondary:
    text: Read the tutorial
    link: /guide/tutorial.html
  meta: v0.4.0 · MIT · PHP ≥ 7.4
---

New here? Read [**Why Maybe?**](/guide/why-maybe) for the trade-offs, follow the [**Tutorial**](/guide/tutorial) to build a validated flow end to end, or keep the [**API Reference**](/guide/api-reference) open while you work.
