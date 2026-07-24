# Security Policy

## Supported Versions

The current `0.4.x` line is the supported version for security review and fixes.

Maybe supports PHP 7.4 for legacy compatibility and tests current supported PHP
branches in CI. PHP 7.4 reached end of life in November 2022; deployments should
prefer PHP 8.2 or newer and must keep the runtime patched by the operating-system
provider.

## Reporting a Vulnerability

Please report security concerns through a private GitHub security advisory or by contacting the repository maintainer.

Do not disclose security issues publicly before maintainers have reviewed the report and had a reasonable opportunity to respond.

When reporting a concern, include:

- A clear description of the issue.
- Steps to reproduce when possible.
- Affected versions or commit references.
- Any known mitigations.

## Application Security Responsibilities

Maybe helps make validation, optional values, and expected business errors explicit. It does not replace application-level security controls such as authentication, authorization, output escaping, CSRF protection, SQL injection prevention, secrets management, logging, monitoring, or dependency vulnerability review.

The `Async` API executes caller-provided code in a child process. Callables,
regular expressions, `php_binary`, `autoload`, and `temp_dir` are trusted
configuration and must not be copied directly from a request. Async IPC files
are private to the current user and authenticated against accidental or
cross-user tampering; this is not a sandbox for a malicious task running as the
same operating-system user. Child processes spawned by a task are not
guaranteed to be terminated by `cancel()` or a timeout.
