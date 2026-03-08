<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Password Helpers';
$page_description = 'Password hashing helper wrappers around PHP password APIs.';
$page_id = 'password';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">namespace: password</span>
    <h1>Password Helpers</h1>
    <p>Thin wrappers for password hashing, verification, rehash checks, and hash metadata.</p>
</section>

<section class="docs-section">
    <h2>Hash and Verify Passwords</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\Password\PasswordAlgorithm;
use function Harbor\Password\argon2id;
use function Harbor\Password\bcrypt;
use function Harbor\Password\password_hash;
use function Harbor\Password\password_needs_rehash;
use function Harbor\Password\password_verify;

$hash = password_hash('my-secret-password', PasswordAlgorithm::BCRYPT, ['cost' => 12]);
$argon_hash = argon2id('my-secret-password');
$legacy_hash = bcrypt('my-secret-password', ['cost' => 10]);

if (! password_verify('my-secret-password', $hash)) {
    // invalid credentials
}

if (password_needs_rehash($hash, PasswordAlgorithm::BCRYPT, ['cost' => 12])) {
    $hash = password_hash('my-secret-password', PasswordAlgorithm::BCRYPT, ['cost' => 12]);
}</code></pre>
    <h3>What it does</h3>
    <p>Wraps PHP's <code>password_*</code> functions with one helper namespace and enum-driven algorithm selection.</p>

    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Password Helper API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <pre><code class="language-php">enum PasswordAlgorithm: string
// Supported helper algorithm values.
// DEFAULT, BCRYPT, ARGON2I, ARGON2ID

function password_hash(string $password, ?PasswordAlgorithm $algorithm = null, array $options = []): string
// Hashes one plain password string.
// algorithm defaults to PasswordAlgorithm::DEFAULT when null.
$hash = password_hash('my-secret-password');
$bcrypt_hash = password_hash('my-secret-password', PasswordAlgorithm::BCRYPT, ['cost' => 12]);

function password_verify(string $password, string $hash): bool
// Verifies a plain password against one stored hash.
$is_valid = password_verify('my-secret-password', $hash);

function password_needs_rehash(string $hash, ?PasswordAlgorithm $algorithm = null, array $options = []): bool
// Checks whether an existing hash should be rehashed.
$needs_rehash = password_needs_rehash($hash, PasswordAlgorithm::BCRYPT, ['cost' => 12]);

function password_info(string $hash): array
// Returns hash metadata (algo, algoName, options).
$info = password_info($hash);

function password_algorithms(): array
// Returns available password algorithm names.
$algorithms = password_algorithms();

function bcrypt(string $password, array $options = []): string
// Shortcut hash helper for bcrypt.
$hash = bcrypt('my-secret-password');

function argon2i(string $password, array $options = []): string
// Shortcut hash helper for argon2i.
$hash = argon2i('my-secret-password');

function argon2id(string $password, array $options = []): string
// Shortcut hash helper for argon2id.
$hash = argon2id('my-secret-password');</code></pre>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
