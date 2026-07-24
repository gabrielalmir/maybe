# Security and memory audit — July 2026

## Scope and threat model

This review covers the `0.3.0` runtime library, with emphasis on `Async`,
serialization, process cleanup, temporary files, validation error aggregation,
Composer dependencies, and GitHub Actions. The threat model includes hostile
web input and a different user/process on the same shared host. A task itself
is trusted application code; `Async` is not a same-user code sandbox.

## Findings addressed

### High — IPC tampering could reach unrestricted deserialization (CWE-502)

The previous implementation exchanged native serialized values through
predictable temporary files and deserialized them without an authenticity
check. A same-host attacker who could replace an input or output file could
trigger object construction and magic methods in the parent or worker.

The implementation now uses a per-run random secret, HMAC-SHA256 envelopes,
private random run directories, exclusive `0600` files, and verifies the MAC
before deserializing. The remaining boundary is the current operating-system
user: a compromised process running as that user can still read or alter the
process's private files.

### High — stdout/stderr pipe saturation could hang a future (CWE-400)

The parent did not drain child pipes while the process was running. A task that
wrote enough output could block forever, retaining the child process and the
future. Output is now redirected to the platform null device.

### Medium — unbounded IPC values could exhaust memory (CWE-789)

Serialization, base64, JSON, file reads, and deserialization created multiple
copies of large values. Input and output limits now default to 16 MiB and 64
MiB, respectively, and can be explicitly disabled for migration.

### Medium — futures retained callbacks and intermediate values (CWE-400)

Callbacks and raw outcomes remained reachable after finalization. Finalization
now clears callback queues, raw values, and the IPC secret; callbacks added after
finalization are ignored without being retained.

### Medium — cancellation did not reliably reap the worker (CWE-772)

Cancellation and timeout now close descriptors, wait briefly after termination,
escalate on POSIX, and always call `proc_close`. Descendants created by the task
remain outside the guarantee.

### Medium — collection of validation errors had avoidable quadratic copying (CWE-400)

`ArraySchema` and `ObjectSchema` repeatedly copied immutable error bags while
processing large invalid inputs. Both now collect errors once and construct a
single bag.

### Low — security policy and dependency lifecycle drift

The policy previously named `0.2.x` while the current release was `0.3.0`.
Composer now allows Opis Closure 4.x on modern PHP while retaining 3.x for PHP
7.4, and Dependabot plus `composer audit` are enabled for ongoing review.

## Residual risks

`Async` executes arbitrary caller callables with the privileges of the PHP
process. It does not provide CPU, filesystem, network, or descendant-process
sandboxing. Configuration options such as `php_binary`, `autoload`, and
`temp_dir` must remain trusted. PHP 7.4 is retained only for compatibility and
should be upgraded wherever possible.
