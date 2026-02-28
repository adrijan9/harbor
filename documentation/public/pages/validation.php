<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Validation Helpers';
$page_description = 'Fluent validation rules and structured validation results.';
$page_id = 'validation';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">namespace: validation</span>
    <h1>Validation Helpers</h1>
    <p>Define fluent validation rules and validate input using structured result objects.</p>
</section>

<section class="docs-section">
    <h2>Define Rules</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Validation\validation_rule;

$email_rule = validation_rule('email')->required()->email();
$name_rule = validation_rule('profile.name')->required()->string()->min(2)->max(120);
$role_rule = validation_rule('role')->in(['admin', 'editor']);</code></pre>
    <h3>What it does</h3>
    <p>Builds one fluent rule chain per field path (including dot-notation fields).</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Rule Builder API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function validation_rule(string $field): ValidationRule
// Creates one fluent rule object for a field path.
$rule = validation_rule('email');

// ValidationRule fluent methods:
required(): ValidationRule
nullable(): ValidationRule
string(): ValidationRule
int(): ValidationRule
float(): ValidationRule
bool(): ValidationRule
array(): ValidationRule
email(): ValidationRule
min(int|float $value): ValidationRule
max(int|float $value): ValidationRule
in(array $allowed_values): ValidationRule
regex(string $pattern): ValidationRule</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Validate Input</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Response\response_validation;
use function Harbor\Validation\validation_errors;
use function Harbor\Validation\validation_has_errors;
use function Harbor\Validation\validation_rule;
use function Harbor\Validation\validation_validate;

$result = validation_validate($payload, [
    validation_rule('email')->required()->email(),
    validation_rule('age')->required()->int()->min(18)->max(65),
    validation_rule('profile.name')->required()->string()->min(2)->max(120),
], [
    'email.required' => 'Email is required.',
]);

if (validation_has_errors($result)) {
    $errors = validation_errors($result);
    response_validation($result); // JSON 422 when client accepts JSON
}</code></pre>
    <h3>What it does</h3>
    <p>Validates an input map against multiple fluent rules and returns a reusable result object.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Validation API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">function validation_validate(array $input, array $rules, array $messages = []): ValidationResult
// Runs all ValidationRule items against input and returns ValidationResult.
$result = validation_validate($input, [$rule1, $rule2]);

function validation_errors(ValidationResult $result): array
// Returns errors keyed by field.
$errors = validation_errors($result);

function validation_has_errors(ValidationResult $result): bool
// True when result includes any errors.
$has_errors = validation_has_errors($result);

// ValidationResult methods:
is_ok(): bool
has_errors(): bool
errors(): array
validated(): array</code></pre>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Direct Rule Validation</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use function Harbor\Validation\validation_rule;

$amount_rule = validation_rule('amount')->required()->float()->min(10)->max(20);

$is_valid_value = $amount_rule->is_valid(12.5);
$is_valid_input = $amount_rule->is_valid_input(['amount' => '17.75']);</code></pre>
    <h3>What it does</h3>
    <p>Validates a single rule directly against one value or one input map.</p>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
