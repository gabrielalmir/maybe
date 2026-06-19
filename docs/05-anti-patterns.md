# Anti-Patterns

This document describes harmful or confusing ways to use Maybe and the safer alternatives teams should prefer.

## 1. Using `unwrap()` Everywhere

**Bad example:**

```php
$email = $emailOption->unwrap();
```

**Why it is bad:** `unwrap()` can fail when the value is absent. If used everywhere, the code returns to hidden failure paths.

**Better alternative:**

```php
$email = $emailOption->match(
    function ($value) { return $value; },
    function () { return 'guest@example.com'; }
);
```

## 2. Replacing Every Exception with Result

**Bad example:**

```php
if (!$db->connect()) {
    return Result::err('database_unavailable');
}
```

**Why it is bad:** Infrastructure failures are not normal business outcomes and may need logging, alerting, or rollback.

**Better alternative:** Keep exceptions for unexpected technical failures and use `Result` for expected business failures.

## 3. Returning `none()` for Technical Failures

**Bad example:**

```php
try {
    return Option::fromNullable($this->db->find($id));
} catch (Throwable $e) {
    return Option::none();
}
```

**Why it is bad:** It hides a database failure as if the record simply did not exist.

**Better alternative:** Let the technical failure surface, log it, or map it through an application-level error strategy.

## 4. Using Option Where a Required Value Should Be Validated

**Bad example:**

```php
$email = Option::fromNullable($input['email'] ?? null);
```

**Why it is bad:** A required email should be validated, not treated as optional.

**Better alternative:**

```php
$schema = Schema::shape(array('email' => Schema::string()->trimmed()->min(5)));
```

## 5. Creating DTOs That Access the Database

**Bad example:**

```php
protected static function fromValidated(array $validated)
{
    $customer = CustomerModel::find($validated['customer_id']);
    return new self($customer);
}
```

**Why it is bad:** DTOs become hard to test and start performing business or infrastructure work.

**Better alternative:** DTOs should hold validated input. Services should load records and enforce business rules.

## 6. Putting Business Logic Inside Controllers

**Bad example:**

```php
if ($order['status'] === 'paid' && $user['role'] !== 'admin') {
    return $this->output->set_status_header(403);
}
```

**Why it is bad:** Business rules become duplicated and difficult to test.

**Better alternative:** Put the rule in a service that returns `Result`, then map that result in the controller.

## 7. Using Async for Trivial Tasks

**Bad example:**

```php
$value = await(async(static function () { return 1 + 1; }));
```

**Why it is bad:** Process startup costs more than the work.

**Better alternative:** Run trivial work directly in the current process.

## 8. Sharing Database Connections with Async Child Processes

**Bad example:**

```php
$db = $this->db;
$future = async(static function () use ($db) { return $db->get('orders')->result_array(); });
```

**Why it is bad:** Database connections and framework objects are not safe to share across process boundaries.

**Better alternative:** Pass scalar IDs or filters and create a new connection inside the child process if needed.

## 9. Using the Library Only to Make Code Look Functional

**Bad example:**

```php
return Option::some(Result::ok($value));
```

**Why it is bad:** Wrapping values without a real modeling need makes code harder to read.

**Better alternative:** Use Maybe where it clarifies validation, expected absence, or expected business errors.

## 10. Wrapping Every Single Value in Option or Result Without Purpose

**Bad example:**

```php
$name = Option::some('Ana');
$age = Result::ok(30);
```

**Why it is bad:** It adds ceremony without improving safety.

**Better alternative:** Use plain values when values are already present and valid. Introduce `Option` or `Result` at meaningful boundaries.
