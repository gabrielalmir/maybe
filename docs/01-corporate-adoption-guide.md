# Corporate Adoption Guide

Maybe is a lightweight PHP library that helps teams make input validation, optional values, and expected business errors explicit and predictable.

This guide is intended for technical leads, analysts, reviewers, and PHP developers evaluating Maybe for legacy PHP applications, including CodeIgniter 3 systems.

## What Problem Maybe Solves

Many legacy PHP systems rely on conventions that are easy to miss during maintenance:

- `null` may mean “not found”, “not loaded”, “not allowed”, or “technical failure”.
- `false` may mean validation failure, database failure, or an empty result.
- Exceptions may be used both for unexpected technical failures and expected business outcomes.
- Request validation may be duplicated across controllers, services, and views.
- Large controllers may mix HTTP handling, validation, authorization, and domain decisions.

Maybe provides small building blocks to make these cases explicit:

- `Schema` parses and validates input.
- `DTO` maps validated input into structured objects.
- `Result` represents success or expected business failure.
- `Option` represents a value that may be absent.
- `Async` supports controlled process-based concurrency when justified.

## Why Explicit Business Logic Matters

Corporate systems often need predictable behavior, auditability, and low-risk change. Explicit validation and error flows help teams:

- Review business rules in pull requests.
- Reduce hidden assumptions around `null`, `false`, and loosely typed arrays.
- Keep controllers focused on HTTP concerns.
- Reuse validation rules across forms, APIs, imports, and reports.
- Distinguish expected business errors from unexpected technical failures.

## How Maybe Helps Legacy PHP Projects

Maybe does not require a framework migration or a rewrite. Teams can introduce it at application boundaries first, then gradually move business flows toward clearer return values.

Recommended low-risk entry points include:

- Form submissions that currently have repeated validation.
- API payloads that use large associative arrays.
- Report filters with optional fields and date ranges.
- Service methods that return `false`, strings, or arrays with ad hoc error keys.
- Repository lookups where “not found” is expected.

## Recommended Adoption Order

Adopt Maybe incrementally in this order:

1. `Schema` — validate and normalize input at boundaries.
2. `DTO` — move validated input into named objects.
3. `Result` — return expected business errors without using exceptions for normal flow.
4. `Option` — make expected absence explicit in lookup code.
5. `Async` — evaluate only after the team understands process isolation and operational risks.

New teams should not begin adoption with `Async`. It is useful, but it requires extra care around resources, timeouts, and serialization.

## Recommended Pilot Project Criteria

Choose a pilot that is important enough to prove value but small enough to review safely:

- One controller or endpoint with repeated validation.
- One request DTO with clear fields.
- One service method that can return a predictable business error.
- One repository lookup where absence is normal.
- A testable flow that does not require infrastructure changes.

Avoid starting with payment settlement, complex imports, queue workers, or cross-system workflows unless the team already has strong tests and rollback plans.

## Adoption Risks

| Risk | Mitigation |
| --- | --- |
| Developers call `unwrap()` everywhere | Require `match()` or explicit branching at boundaries. |
| Teams wrap every value without purpose | Use Maybe only where absence, validation, or expected errors are real concerns. |
| Exceptions are replaced too aggressively | Keep exceptions for unexpected technical failures. |
| DTOs become framework-aware | Keep DTOs independent from controllers, sessions, databases, and request globals. |
| Async is used for trivial work | Require explicit justification and timeout behavior. |

## Team Training Recommendations

A short adoption workshop should cover:

- Reading a `Schema` and understanding validation errors.
- Creating a DTO from validated request data.
- Returning `Result::ok()` and `Result::err()` from services.
- Handling `Option::some()` and `Option::none()` in repository lookups.
- Knowing when exceptions are still appropriate.
- Understanding why `Async` has process and serialization boundaries.

## Pull Request Review Checklist

Use this checklist when reviewing Maybe adoption changes:

- [ ] Is input validation centralized in a reusable `Schema` or DTO?
- [ ] Does the DTO avoid framework state, database access, and side effects?
- [ ] Are expected business failures returned as `Result` instead of thrown exceptions?
- [ ] Are unexpected technical failures still allowed to fail loudly?
- [ ] Is `Option` used only for expected absence?
- [ ] Are controllers limited to request parsing, service calls, and response mapping?
- [ ] Are `unwrap()` calls limited to tests, scripts, or places with clear prior checks?
- [ ] If `Async` is used, is there explicit justification, timeout handling, and resource isolation?
