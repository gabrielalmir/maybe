# Usage Patterns

This document defines a recommended internal usage policy for teams adopting Maybe in legacy PHP applications.

## Controllers

Controllers:

- May use `DTO::fromArray()`.
- Should convert `Result` into HTTP responses, redirects, or views.
- Should not contain complex business rules.
- Should not perform manual validation if a reusable DTO or schema exists.

Example:

```php
$result = CreateCustomerDTO::fromArray($this->input->post(null, true));

return $result->match(
    function (CreateCustomerDTO $dto) {
        return $this->customerService->create($dto)->match(
            function ($customer) {
                return $this->output->set_content_type('application/json')->set_output(json_encode($customer));
            },
            function ($error) {
                return $this->output->set_status_header(422)->set_output(json_encode(array('error' => $error)));
            }
        );
    },
    function ($errors) {
        return $this->output->set_status_header(422)->set_output(json_encode(array('errors' => $errors->toArray())));
    }
);
```

## Services

Services:

- Should return `Result` when an error is expected and part of the business flow.
- Should not throw exceptions for normal validation or business rule failures.
- May use `Option` when a dependency returns an optional value.

Use `Result` for cases such as duplicate customer, permission denied, invalid state transition, or a closed accounting period.

## Repositories

Repositories:

- May return `Option` when a record may not exist.
- Should throw exceptions only for unexpected technical failures.
- Should not hide database failures as `none()`.

A missing customer can be `Option::none()`. A failed database connection should remain an exception or another operational failure signal.

## DTOs

DTOs:

- Must represent validated input.
- Must not access the database.
- Must not access framework state.
- Must not perform business side effects.

DTOs should be portable PHP objects that can be tested without booting CodeIgniter or another framework.

## Async

Async:

- Must be used only with explicit justification.
- Must not share database connections or external resources between parent and child processes.
- Must recreate non-serializable resources inside the child process.
- Must define timeout behavior when appropriate.

Async is an operational tool for independent work. It is not the default way to structure normal request handling.
