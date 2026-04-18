# Unsigned Numeric Helpers Plan

Date: 2026-04-18

## Goal

Add strict unsigned numeric helper variants to the existing public numeric helper API without breaking current `*_int()` and `*_float()` behavior.

The current signed helpers mostly return a default value when conversion fails. The new unsigned helpers must be stricter:

- keep existing signed helpers unchanged for backward compatibility
- add new unsigned helpers with one canonical naming scheme
- throw an exception when a provided numeric value is negative or otherwise violates the unsigned contract
- add PHPUnit coverage
- add/update docs in every affected documentation module

## Naming Decision

Use these canonical suffixes:

- `*_uint()` for unsigned integers
- `*_ufloat()` for unsigned floats

Do not add duplicate aliases. Existing `*_float()` helpers already exist, so the unsigned float variant should be `*_ufloat()`, not another spelling of `*_float()`.

## Shared Implementation Decision

Do not copy unsigned parsing logic into every module.

The implementation should be centralized in one shared support file and reused everywhere. The current Harbor structure already has shared support modules such as:

- `src/Support/value.php`
- `src/Support/string.php`
- `src/Support/array.php`

So the unsigned parsing logic should follow the same direction.

Recommended structure:

- add `src/Support/number.php` as the shared numeric support module

If the team strongly wants the filename to be `numbers.php`, that is workable, but `number.php` is more consistent with the existing singular support module naming.

The important part is the architecture, not the exact filename:

- one shared support file owns the unsigned parsing and validation rules
- the shared support file should expose public conversion helpers too, so users can call number parsing directly without going through `request_*`, `config_*`, `route_*`, or `command_*`
- public module helpers stay thin and only resolve missing values/defaults
- no repeated `is_numeric()` / negative-check / decimal-rejection code in every module

## Current Public Numeric Helper Inventory

### Primary modules that should gain unsigned variants

| Module | Current public numeric helpers | Source file | Existing tests | Existing docs |
| --- | --- | --- | --- | --- |
| Config | `config_int()`, `config_float()` | `src/Config/config.php` | `tests/Config/ConfigHelpersTest.php` | `documentation/public/pages/config.php` |
| Request | `request_header_int()`, `request_header_float()`, `request_body_int()`, `request_body_float()`, `request_input_int()`, `request_input_float()` | `src/Request/request.php` | `tests/Request/RequestHelpersTest.php` | `documentation/public/pages/request.php` |
| Router | `route_segment_int()`, `route_segment_float()`, `route_query_int()`, `route_query_float()` | `src/Router/helpers/route_segments.php`, `src/Router/helpers/route_query.php` | `tests/Router/RouteHelpersTest.php` | `documentation/public/pages/routing.php` |
| Command entry | `command_arg_int()`, `command_arg_float()` | `src/Command/command_entry_helpers.php` | `tests/Command/CommandHelpersTest.php` | `documentation/public/pages/commands.php` |
| Command flags | `command_flag_int()`, `command_flag_float()` | `src/Command/flags/command_flags.php` | `tests/Command/CommandFlagsHelpersTest.php` | `documentation/public/pages/commands.php` |

### Secondary public number-related modules to document in the plan, but not mirror with `_uint()` / `_ufloat()` in the first pass

#### Units

`src/Units/units.php` exposes public number-focused helpers:

- `unit_kb_from_bytes()`
- `unit_mb_from_bytes()`
- `unit_gb_from_bytes()`
- `unit_tb_from_bytes()`
- `unit_bytes_from_kb()`
- `unit_bytes_from_mb()`
- `unit_bytes_from_gb()`
- `unit_bytes_from_tb()`
- `unit_kb_from_mb()`
- `unit_mb_from_kb()`
- `unit_mb_from_gb()`
- `unit_gb_from_mb()`
- `unit_gb_from_tb()`
- `unit_tb_from_gb()`
- `unit_bytes_to_human()`
- `unit_duration_ms_to_human()`

These are public number-related helpers, but they are not good candidates for a direct `_uint()` / `_ufloat()` family in the first rollout:

- `unit_bytes_to_human()` and `unit_duration_ms_to_human()` currently support negative values by design
- the conversion API is domain-specific math, not typed input retrieval
- adding unsigned clones here would create a second API direction that is not clearly needed yet

Tests and docs already confirm signed behavior here:

- `tests/Units/UnitsHelpersTest.php`
- `documentation/public/pages/units.php`

### Adjacent numeric public APIs that are not part of the unsigned-helper rollout

These are public numeric helpers or counters, but they do not fit the new typed unsigned accessor family:

- `config_count()`
- `request_port()`
- `request_body_count()`
- `route_segments_count()`
- `route_queries_count()`
- cache count helpers such as `cache_count()`, `cache_array_count()`, `cache_apc_count()`, `cache_file_count()`

They should stay as-is unless there is a separate API cleanup pass.

## Required New Public APIs

### Config

- `config_uint(string $key, int $default = 0): int`
- `config_ufloat(string $key, float $default = 0.0): float`

### Request

- `request_header_uint(string $key, int $default = 0): int`
- `request_header_ufloat(string $key, float $default = 0.0): float`
- `request_body_uint(string $key, int $default = 0): int`
- `request_body_ufloat(string $key, float $default = 0.0): float`
- `request_input_uint(string $key, int $default = 0): int`
- `request_input_ufloat(string $key, float $default = 0.0): float`

### Router

- `route_segment_uint(int $index, int $default = 0): int`
- `route_segment_ufloat(int $index, float $default = 0.0): float`
- `route_query_uint(string $key, int $default = 0): int`
- `route_query_ufloat(string $key, float $default = 0.0): float`

### Command entry

- `command_arg_uint(int $index, int $default = 0): int`
- `command_arg_ufloat(int $index, float $default = 0.0): float`

### Command flags

- `command_flag_uint(array &$command, string $flag, bool $require_value, string $description, ?ValidationRule $validator = null, int $default_value = 0): int`
- `command_flag_ufloat(array &$command, string $flag, bool $require_value, string $description, ?ValidationRule $validator = null, float $default_value = 0.0): float`

## Required Behavior

### Backward compatibility

- keep all existing `*_int()` and `*_float()` helpers unchanged
- do not silently tighten the existing signed helpers
- unsigned behavior belongs only in the new public helpers

### Unsigned integer rules

`*_uint()` should:

- accept `0`
- accept positive integers
- accept integer-like numeric strings such as `"0"` and `"42"`
- reject negative integers and negative numeric strings
- reject decimals such as `1.5` and `"1.5"`
- reject non-numeric input
- reject invalid negative defaults

### Unsigned float rules

`*_ufloat()` should:

- accept `0`, `0.0`, positive integers, and positive floats
- accept numeric strings such as `"0"`, `"2"`, and `"2.75"`
- reject negative integers, negative floats, and negative numeric strings
- reject non-numeric input
- reject invalid negative defaults

### Missing values vs invalid values

The stricter unsigned helpers still need predictable defaults, but only for the missing-value path:

- if the requested key/index/value is missing, return the provided default
- if the resolved value exists but is invalid for the unsigned contract, throw
- if the provided default itself violates the unsigned contract, throw

This preserves the ergonomics of the current API while enforcing unsigned guarantees when a value is actually present.

### Exception type

Recommended exception split:

- `\InvalidArgumentException` for `Config`, `Request`, `Router`, and command positional argument helpers
- `Harbor\Command\CommandInvalidFlagException` for `command_flag_uint()` and `command_flag_ufloat()` when the CLI flag value is invalid
- `\InvalidArgumentException` for invalid developer-provided defaults in any module

### Error message guidance

Messages should identify the helper contract clearly. Example style:

- `config_uint() expects an unsigned integer.`
- `request_body_ufloat() expects an unsigned float.`
- `--limit expects an unsigned integer value.`

Include the key, index, or flag name when possible.

## Private Implementation Plan

Each public module should call one shared support implementation instead of duplicating numeric parsing logic.

### Shared support module

Add a new support helper file:

- `src/Support/number.php`

Recommended public helpers inside that file:

- `number_uint(mixed $value): int`
- `number_ufloat(mixed $value): float`

Recommended internal helpers inside that file:

- `number_internal_value_to_uint(mixed $value, string $context = 'value'): int`
- `number_internal_value_to_ufloat(mixed $value, string $context = 'value'): float`

Optional helpers if the implementation reads better split out:

- `number_internal_is_unsigned_integer_string(string $value): bool`
- `number_internal_is_unsigned_numeric_string(string $value): bool`
- `number_internal_invalid_unsigned_exception(string $context, string $type): \InvalidArgumentException`

These support helpers should be the single source of truth for:

- non-negative checks
- decimal rejection for `uint`
- numeric-string parsing
- consistent exception messages

### Thin wrappers in public modules

Module-level public wrappers should stay small:

- resolve whether the requested key/index/flag is missing
- return the provided default when the value is missing
- validate the default through the shared support helper
- pass present values into the shared support helper
- translate exceptions only where the module needs a module-specific exception type

Example:

- `config_uint()` should call `number_internal_value_to_uint()` after reading the config value
- `request_body_ufloat()` should call `number_internal_value_to_ufloat()` after reading the request value
- `route_query_uint()` should call the same shared support function after resolving the query key
- `command_arg_uint()` should call the same shared support function after resolving the positional argument
- `command_flag_uint()` should call the same shared support function, but convert invalid-value failures into `CommandInvalidFlagException`

### Module-local helpers only where needed

Per-module private helpers are still acceptable for wrapper-only concerns, but not for duplicated number parsing.

Good module-local helper examples:

- `request_internal_key_exists_for_unsigned_reader(...)`
- `route_query_internal_has_present_value(...)`
- `command_flags_internal_wrap_unsigned_exception(...)`

Bad duplication:

- a second copy of `is_numeric()` parsing in `config.php`
- a third copy of unsigned float validation in `request.php`
- another bespoke negative-value parser in `route_query.php`

## Tests To Add Or Update

### Shared expectations to cover everywhere

- zero is valid
- positive integer and positive float cases work
- missing values use the provided default
- negative values throw
- non-numeric values throw
- `*_uint()` rejects decimal values
- invalid negative defaults throw

### Files to update

- `tests/Config/ConfigHelpersTest.php`
  - add `config_uint()` and `config_ufloat()` success/default/exception coverage
- `tests/Request/RequestHelpersTest.php`
  - add header/body/input unsigned helper coverage
- `tests/Router/RouteHelpersTest.php`
  - add segment/query unsigned helper coverage
- `tests/Command/CommandHelpersTest.php`
  - add runtime helper availability checks for `command_arg_uint()` and `command_arg_ufloat()`
  - add positional argument parsing coverage
- `tests/Command/CommandFlagsHelpersTest.php`
  - add `command_flag_uint()` and `command_flag_ufloat()` success/default/exception coverage
- `tests/HelperTest.php`
  - assert new public helper functions are registered after helper loading

## Documentation Modules To Update

### Config docs

File: `documentation/public/pages/config.php`

Required updates:

- add `config_uint()` and `config_ufloat()` examples
- add API entries that explain the strict unsigned contract
- explain that invalid present values throw instead of falling back

### Request docs

File: `documentation/public/pages/request.php`

Required updates:

- add unsigned helpers in the `Headers`, `Body`, and `Input` API groups
- show examples for zero and positive values
- explicitly document exception behavior for negative input

### Routing docs

File: `documentation/public/pages/routing.php`

Required updates:

- add `route_segment_uint()`, `route_segment_ufloat()`, `route_query_uint()`, and `route_query_ufloat()`
- explain that the new helpers are stricter than `route_segment_int()` / `route_query_int()`
- document thrown exceptions for negative matched values

### Commands docs

File: `documentation/public/pages/commands.php`

Required updates:

- add `command_arg_uint()` and `command_arg_ufloat()` to the entry helper section
- add `command_flag_uint()` and `command_flag_ufloat()` to the flag helper section
- explain that invalid unsigned CLI values raise exceptions instead of being accepted as signed values

### Units docs

File: `documentation/public/pages/units.php`

No new unsigned helper additions are planned in the first pass.

The page should only be updated if the product decision changes and we explicitly choose to tighten or fork the units API. Right now the current signed behavior is intentional and should stay documented as-is.

### Docs search index

After changing any docs page under `documentation/public/pages/`, re-run:

```bash
./bin/harbor-docs-index
```

Then commit the updated:

- `documentation/public/assets/search-index.json`

## Follow-up Audit After The Public API Is Added

After the public unsigned helpers exist, audit internal consumers that currently use signed helpers for values that are naturally non-negative, for example:

- pagination page numbers
- TTL / timeout configuration
- rate-limit numeric inputs

This should be a separate compatibility review, not folded into the initial helper-addition commit.
