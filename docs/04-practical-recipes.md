# Practical Recipes

These recipes are intentionally small so teams can copy, adapt, and standardize them in legacy PHP projects.

## 1. Validating a Simple Form Request

**Problem:** A controller repeats validation and trimming.

**Before:**

```php
$email = trim($this->input->post('email'));
if ($email === '') {
    return $this->output->set_status_header(422);
}
```

**After:**

```php
use Maybe\Schema\Schema;

$result = Schema::shape(array(
    'email' => Schema::string()->trimmed()->min(5),
))->safeParse($this->input->post(null, true));
```

**Why this is better:** Validation is reusable and returns structured success or error information.

**When not to use this pattern:** Do not add a new schema for a one-line internal value that is already guaranteed by earlier validation.

## 2. Validating Report Filters

**Problem:** Optional filters are handled inconsistently.

**Before:**

```php
$status = $_GET['status'] ?? null;
```

**After:**

```php
$filters = Schema::shape(array(
    'status' => Schema::option(Schema::enumeration(array('open', 'closed'))),
    'from' => Schema::option(Schema::date()),
))->safeParse($this->input->get(null, true));
```

**Why this is better:** Optional fields and allowed values are documented in one place.

**When not to use this pattern:** Do not use it to bypass authorization or data access checks.

## 3. Creating a DTO from Request Data

**Problem:** Services receive raw request arrays.

**Before:**

```php
$this->customer_service->create($this->input->post(null, true));
```

**After:**

```php
$dtoResult = CreateCustomerDTO::fromArray($this->input->post(null, true));
```

**Why this is better:** The service can depend on validated, named fields instead of raw arrays.

**When not to use this pattern:** Do not create DTOs that simply duplicate every database column without representing a real boundary.

## 4. Returning a Business Error with Result

**Problem:** A service throws an exception for an expected rule.

**Before:**

```php
if ($account->locked) {
    throw new RuntimeException('account_locked');
}
```

**After:**

```php
use Maybe\Result\Result;

if ($account->locked) {
    return Result::err('account_locked');
}

return Result::ok($account);
```

**Why this is better:** The controller can map expected errors to user-friendly responses.

**When not to use this pattern:** Keep exceptions for unexpected failures such as broken configuration or database outages.

## 5. Returning an Optional Customer Lookup with Option

**Problem:** `null` is returned when a customer is not found.

**Before:**

```php
return $row ?: null;
```

**After:**

```php
use Maybe\Option\Option;

return $row ? Option::some($row) : Option::none();
```

**Why this is better:** Callers must handle both found and not-found cases.

**When not to use this pattern:** Do not return `none()` when the database query itself failed.

## 6. Converting a Legacy `null` Return into Option

**Problem:** A legacy function returns an array or `null`.

**Before:**

```php
$customer = legacy_find_customer($id);
```

**After:**

```php
$customer = Option::fromNullable(legacy_find_customer($id));
```

**Why this is better:** The rest of the code can use `match()` or `unwrapOr()` explicitly.

**When not to use this pattern:** Do not wrap required values that should have been validated earlier.

## 7. Converting a Legacy `false|string` Return into Result

**Problem:** A legacy function returns `false` or an error message string.

**Before:**

```php
$error = legacy_validate_order($order);
if ($error !== false) {
    return $error;
}
```

**After:**

```php
$error = legacy_validate_order($order);
$result = $error === false ? Result::ok($order) : Result::err($error);
```

**Why this is better:** Success and failure are represented by one predictable type.

**When not to use this pattern:** Do not hide exceptions thrown by the legacy function unless they are truly expected business outcomes.

## 8. Standardizing JSON Error Responses

**Problem:** Controllers return inconsistent error shapes.

**Before:**

```php
return $this->output->set_output('Invalid customer');
```

**After:**

```php
return $result->match(
    function ($data) {
        return $this->output->set_content_type('application/json')->set_output(json_encode(array('data' => $data)));
    },
    function ($error) {
        return $this->output->set_status_header(422)->set_content_type('application/json')->set_output(json_encode(array('error' => $error)));
    }
);
```

**Why this is better:** API clients receive a stable response contract.

**When not to use this pattern:** Do not expose internal exception messages directly to clients.

## 9. Using `Schema::enumeration()` for Status Fields

**Problem:** Status values are validated with scattered string comparisons.

**Before:**

```php
if ($status !== 'draft' && $status !== 'approved') {
    return false;
}
```

**After:**

```php
$statusSchema = Schema::enumeration(array('draft', 'approved'));
$result = $statusSchema->safeParse($status);
```

**Why this is better:** Allowed values are declared in one reusable schema.

**When not to use this pattern:** Do not use an enum schema for values that must come from a database permission model.

## 10. Using `Schema::option()` for Optional Fields

**Problem:** Optional request fields are confused with invalid required fields.

**Before:**

```php
$middleName = isset($input['middle_name']) ? trim($input['middle_name']) : null;
```

**After:**

```php
$schema = Schema::shape(array(
    'middle_name' => Schema::option(Schema::string()->trimmed()),
));
```

**Why this is better:** The schema makes absence explicit while still validating present values.

**When not to use this pattern:** Do not make required business data optional just to avoid validation errors.
