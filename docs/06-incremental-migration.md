# Incremental Migration Guide

Maybe is designed for gradual adoption. Teams can improve one boundary, controller, service, or repository at a time without changing the whole application architecture.

## Phase 1 — Identify Pain Points

Look for:

- Repeated validation.
- Large controllers.
- Methods returning `null`, `false`, or strings as errors.
- Duplicated request parsing.
- Business rules mixed with HTTP concerns.
- Unclear error handling.

Good first candidates are flows that already cause support tickets or code review confusion.

## Phase 2 — Introduce DTOs at Boundaries

Start with:

- Form submissions.
- API payloads.
- Report filters.
- Import files.
- Integration payloads.

Use `Schema` to validate raw arrays and `DTO` to represent the validated data. Keep the DTO independent from controllers and framework services.

## Phase 3 — Use Result in Services

Use `Result` for:

- Expected business rule failure.
- Permission denial.
- Invalid state transition.
- Duplicate record.
- Validation failure from a domain rule.

Keep exceptions for unexpected technical failures such as unavailable databases, corrupted configuration, or failed infrastructure calls.

## Phase 4 — Use Option in Repositories

Use `Option` for:

- `findById`.
- `findByEmail`.
- `findActiveContract`.
- Any lookup where absence is expected.

Do not use `Option::none()` to hide database errors. A lookup that cannot be completed is different from a lookup that completed and found no record.

## Phase 5 — Evaluate Async Carefully

Use `Async` only for:

- Independent tasks.
- External calls.
- Controlled parallel processing.
- Non-critical background-style workloads where process overhead is acceptable.

Before adopting `Async`, confirm that each task can recreate required resources inside the child process and that timeout behavior is documented.

## Suggested Rollout Plan

1. Add one DTO and schema for a low-risk form.
2. Update the controller to call `DTO::fromArray()`.
3. Move business decisions into a service that returns `Result`.
4. Update one repository lookup to return `Option` for expected absence.
5. Add examples to team documentation before repeating the pattern elsewhere.
