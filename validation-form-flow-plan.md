# Validation Form Flow Plan

## Goal

Implement redirect-friendly form flow helpers for:

- Validation error bags (default + named bags).
- Old input persistence across one redirect cycle.
- Simple view/template retrieval APIs for errors and old values.

This plan covers only design and implementation scope for:

- `[x] Validation error bag + old-input helpers for form flows`

from `plan.md`.

## Proposed Public API

### `namespace Harbor\Validation`

```php
function validation_form_flash(ValidationResult $result, string $bag = 'default'): bool
// Flashes ValidationResult::errors() into a session flash error bag.

function validation_form_errors(string $bag = 'default'): array
// Returns flashed errors for one bag as: array<string, array<int, string>>.

function validation_form_has_errors(string $bag = 'default'): bool
// True when the bag has at least one field error.

function validation_form_field_errors(string $field, string $bag = 'default'): array
// Returns all messages for one field from a bag.

function validation_form_first_error(string $field, string $bag = 'default', ?string $default = null): ?string
// Returns first message for a field, or default.

function validation_form_clear(string $bag = 'default'): bool
// Clears one flashed error bag.
```

### `namespace Harbor\Request`

```php
function request_flash_old_input(?array $input = null, array $except = ['password', 'password_confirmation', 'current_password', '_token'], string $bag = 'default'): bool
// Flashes old input. Uses request_body_all() when input is null.

function request_old(?string $key = null, mixed $default = null, string $bag = 'default'): mixed
// Reads old input from flashed data. Null key returns full old input array.

function request_has_old(string $key, string $bag = 'default'): bool
// Checks if one old input key exists (dot notation supported).

function request_clear_old_input(string $bag = 'default'): bool
// Clears one old-input bag.
```

## Class Design

- No new class is required for MVP.
- Reuse existing `ValidationResult`.
- Keep form-flow state as normalized arrays in flash storage to match Harbor helper style.

## Storage Contract (Session Flash)

- Error bag flash key format: `__validation_form_errors_{bag}`.
- Old input flash key format: `__validation_form_old_input_{bag}`.
- Bag normalization:
  - Trim.
  - Blank bag falls back to `default`.
  - Keep one canonical bag name, no aliases.
- Data shape:
  - Errors: `array<string, array<int, string>>`.
  - Old input: `array<string, mixed>`.

## Internal Helper Functions (Private)

Add private helpers under `/** Private */` sections and follow `module_internal_*` pattern.

### `src/Validation/validation.php`

- `validation_internal_form_bag_name(string $bag): string`
- `validation_internal_form_errors_flash_key(string $bag): string`
- `validation_internal_form_errors_normalize(array $errors): array`

### `src/Request/request.php`

- `request_internal_old_input_bag_name(string $bag): string`
- `request_internal_old_input_flash_key(string $bag): string`
- `request_internal_old_input_normalize(array $input, array $except): array`

## User Flow Design

### POST handler (validation fail path)

```php
use function Harbor\Request\request_body_all;
use function Harbor\Request\request_flash_old_input;
use function Harbor\Response\response_header;
use function Harbor\Response\response_status;
use function Harbor\Validation\validation_form_flash;
use function Harbor\Validation\validation_has_errors;
use function Harbor\Validation\validation_rule;
use function Harbor\Validation\validation_validate;

$input = request_body_all();
$result = validation_validate($input, [
    validation_rule('email')->required()->email(),
    validation_rule('password')->required()->string()->min(8),
]);

if (validation_has_errors($result)) {
    validation_form_flash($result);
    request_flash_old_input($input);
    response_header('Location', '/register');
    response_status(302);
    exit;
}
```

### GET form render

```php
use function Harbor\Request\request_old;
use function Harbor\Validation\validation_form_first_error;

$email_value = request_old('email', '');
$email_error = validation_form_first_error('email', 'default', '');
```

### Named bag support example

```php
validation_form_flash($result, 'profile');
request_flash_old_input($input, ['password', '_token'], 'profile');

$name_value = request_old('name', '', 'profile');
$name_error = validation_form_first_error('name', 'profile', '');
```

## Implementation Checklist (Files)

- [x] `src/Validation/validation.php`
  - Add form error-bag helpers + private internals.
  - Require `src/Session/session.php` for flash integration.
- [x] `src/Request/request.php`
  - Add old-input helpers + private internals.
  - Require `src/Session/session.php` for flash integration.
- [x] `tests/Validation/ValidationHelpersTest.php` or new focused validation form-flow test file.
  - Cover flash/read/clear/default bag/named bag behavior.
- [x] `tests/Request/RequestHelpersTest.php` or new focused old-input test file.
  - Cover flash old input, key lookup, dot lookup, except filtering, clear behavior.
- [x] `tests/HelperTest.php`
  - Add `function_exists()` assertions for all new helpers.
- [x] `documentation/public/pages/validation.php`
  - Add form error-bag helper section and examples.
- [x] `documentation/public/pages/request.php`
  - Add old-input helper section and examples.
- [x] `documentation/public/pages/session.php`
  - Add short note that form flow helpers are backed by flash session data.
- [x] `documentation/public/assets/search-index.json`
  - Regenerate with `./bin/harbor-docs-index`.

## Testing Strategy

- Unit-level helper tests for both namespaces.
- Cross-request simulation via `$_SERVER['REQUEST_TIME_FLOAT']` transitions to validate flash lifecycle.
- Run:
  - `composer test`
  - Optional targeted runs while iterating:
    - `./vendor/bin/phpunit tests/Validation`
    - `./vendor/bin/phpunit tests/Request`

## Non-Goals (MVP)

- No redirect helper API in this scope.
- No new framework-level class abstractions for form state.
- No multi-request persistence beyond flash lifecycle.

## Risks / Notes

- Cookie session driver has payload size limits; large old-input payloads can be truncated by browser cookie constraints.
- Recommended runtime for form-heavy pages: file session driver.
