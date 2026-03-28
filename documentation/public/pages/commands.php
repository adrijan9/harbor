<?php

declare(strict_types=1);

$page_title = 'Harbor Docs - Commands';
$page_description = 'Create, run, and compile custom site commands with harbor-command and .commands.';
$page_id = 'commands';

require __DIR__.'/../shared/header.php';
?>

<section class="hero">
    <span class="hero-eyebrow">Tooling</span>
    <h1>Commands</h1>
    <p>Use <code>harbor-command</code> to define, compile, and run custom site commands from your Harbor project root.</p>
</section>

<section class="docs-section">
    <h2>Quick Start</h2>
    <h3>Example</h3>
    <pre><code class="language-bash">cd my-site
../bin/harbor-command create users:sync
../bin/harbor-command run users:sync -- --dry-run</code></pre>
    <h3>What it does</h3>
    <p>Creates <code>.commands</code>, creates <code>commands/users_sync.php</code>, compiles <code>commands/commands.php</code>, then runs the selected key.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Quick Start API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>cd my-site</code> Command system must run from a Harbor site root (directory that contains <code>.router</code>).</li>
                <li><code>create users:sync</code> Key format: lowercase letters/numbers with <code>_</code>, <code>-</code>, and <code>:</code>.</li>
                <li><code>run users:sync -- ...</code> Everything after <code>--</code> is forwarded to the command entry script as argv values.</li>
            </ul>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Create Commands</h2>
    <h3>Example</h3>
    <pre><code class="language-bash">cd my-site
../bin/harbor-command create cache:warm
../bin/harbor-command create users:import --entry=commands/import_users.php --name="Import Users" --description="Import users from CSV" --timeout=120
../bin/harbor-command create debug:sample --disabled</code></pre>
    <h3>What it does</h3>
    <p>Appends command blocks to <code>.commands</code>, creates missing entry files, and compiles a fresh command registry. Generated stubs include <code>require __DIR__."/../../vendor/autoload.php";</code>, call <code>Helper::Command-&gt;load()</code>, and import command helpers from <code>Harbor\Command</code>.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Create API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>create &lt;key&gt;</code> Creates default entry file at <code>commands/&lt;key-with-colons-replaced-by-underscores&gt;.php</code>.</li>
                <li><code>--entry=path</code> Overrides command entry path (relative to site root, or absolute path).</li>
                <li><code>--name=value</code> Optional display name in registry.</li>
                <li><code>--description=value</code> Optional description in registry.</li>
                <li><code>--timeout=seconds</code> Optional positive integer timeout for run execution.</li>
                <li><code>--disabled</code> Stores command with <code>enabled: false</code>. Use <code>--enabled</code> to force enabled.</li>
                <li><code>Command key rules</code> Cannot use reserved keys: <code>create</code>, <code>run</code>, <code>compile</code>, <code>list</code>, <code>show</code>, <code>delete</code>, <code>update</code>, <code>help</code>.</li>
            </ul>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Run Commands</h2>
    <h3>Example</h3>
    <pre><code class="language-bash">cd my-site
../bin/harbor-command run users:import -- --file=storage/users.csv --chunk=500
../bin/harbor-command --debug run users:import</code></pre>
    <h3>What it does</h3>
    <p>Loads <code>commands/commands.php</code>, resolves entry file for the key, then executes it as a PHP process.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Run API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>run &lt;key&gt;</code> Executes one compiled command definition by key.</li>
                <li><code>Registry auto-rebuild</code> If <code>commands/commands.php</code> is missing and <code>.commands</code> exists, Harbor recompiles automatically before running.</li>
                <li><code>Disabled command</code> Commands with <code>enabled: false</code> are blocked from execution.</li>
                <li><code>Runtime helpers</code> Command entry files can import helpers like <code>command_info()</code>, <code>command_arg_string()</code>, and <code>command_flag()</code> from <code>Harbor\Command</code> after autoload + <code>Helper::Command-&gt;load()</code> (included in generated stubs).</li>
                <li><code>Exit codes</code> Missing key or missing registry returns specific non-zero exits; process errors and timeouts fail execution.</li>
                <li><code>--debug</code> or <code>-v</code> Prints debug diagnostics (paths, execution context, timeout).</li>
            </ul>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Command Entry Helpers</h2>
    <h3>Example</h3>
    <pre><code class="language-php">&lt;?php

declare(strict_types=1);

use Harbor\Helper;
use function Harbor\Command\command_arg_string;
use function Harbor\Command\command_debug;
use function Harbor\Command\command_error;
use function Harbor\Command\command_info;

require __DIR__."/../../vendor/autoload.php";
Helper::Command->load();

$name = command_arg_string(0, 'world');

command_info(sprintf('Hello %s', $name));
command_debug('Ready to execute command logic.');
command_error('Example STDERR message.');</code></pre>
    <h3>What it does</h3>
    <p>Gives command entry scripts reusable runtime helpers for output, debug logging, and positional arguments after loading autoload and calling <code>Helper::Command-&gt;load()</code>.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Entry Helper API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>command_info(string $message): void</code> Writes one line to STDOUT.</li>
                <li><code>command_error(string $message): void</code> Writes one line to STDERR.</li>
                <li><code>command_debug(string $message): void</code> Writes debug output when debug is enabled.</li>
                <li><code>command_raw_arguments(): array</code> Raw argv values forwarded after the command entry file path.</li>
                <li><code>command_arguments(): array</code> Positional arguments only (options excluded).</li>
                <li><code>command_arg(int $index, mixed $default = null): mixed</code> Positional argument by index.</li>
                <li><code>command_arg_string|command_arg_int|command_arg_float|command_arg_bool(...)</code> Typed positional argument helpers.</li>
                <li><code>Namespace</code> Import these functions from <code>Harbor\Command</code> using <code>use function Harbor\Command\...</code>.</li>
            </ul>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Command Flag API</h2>
    <h3>Example</h3>
    <pre><code class="language-php">&lt;?php

declare(strict_types=1);

use Harbor\Helper;
use Harbor\Validation\ValidationRule;
use function Harbor\Command\command_flag;
use function Harbor\Command\command_flag_array;
use function Harbor\Command\command_flag_bool;
use function Harbor\Command\command_flag_float;
use function Harbor\Command\command_flag_int;
use function Harbor\Command\command_flag_string;
use function Harbor\Command\command_init;
use function Harbor\Command\command_flags_print_usage;
use function Harbor\Command\command_info;

require __DIR__."/../../vendor/autoload.php";
Helper::Command->load();

$command = command_init('users:sync', $argc ?? 0, $argv ?? []);
$help = command_flag_bool($command, '--help', 'Display command usage', default_value: false);
$probe = command_flag($command, '--probe', 'Presence-only flag');
$name = command_flag_string($command, '--name', 'User name', default_value: 'world');
$force = command_flag_bool($command, '--force', 'Enable force mode', default_value: false);
$limit = command_flag_int($command, '--limit', 'Chunk size', default_value: 100);
$ratio = command_flag_float($command, '--ratio', 'Ratio value', default_value: 1.5);
$ids = command_flag_array($command, '-p', 'Player ids', default_value: [1, 2, 3, 4]);

// validator with required constraint
$player = command_flag_string(
    $command,
    '--player',
    'Player name',
    validator: (new ValidationRule('player'))->required()->string()->min(2)
);

// validator with in-list constraint
$environment = command_flag_string(
    $command,
    '--env',
    'Environment',
    validator: (new ValidationRule('environment'))->required()->string()->in(['dev', 'stage', 'prod'])
);

if ($help) {
    command_flags_print_usage($command);
    exit(0);
}

command_info(
    sprintf(
        'Running users:sync for %s (force=%s probe=%s player=%s env=%s)',
        $name,
        $force ? 'true' : 'false',
        true === $probe ? 'present' : 'missing',
        $player,
        $environment
    )
);</code></pre>
    <h3>What it does</h3>
    <p>Provides a dedicated flag-definition API for command entry scripts. Any user-created command can use it after loading <code>Helper::Command-&gt;load()</code> (generated command stubs already include this).</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Flag Helper API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>command_init(string $name, int $argc, array $argv): array</code> Initializes command flag context.</li>
                <li><code>command_flag(array &$command, string $flag, string $description, ?ValidationRule $validator = null, mixed $default_value = null): mixed</code> Presence helper. Returns <code>true</code> or a parsed value when present, and <code>null</code> when the flag token is not passed.</li>
                <li><code>command_flag_string|command_flag_int|command_flag_float|command_flag_bool(...)</code> Typed scalar flag helpers.</li>
                <li><code>command_flag_array(...)</code> Array flag helper using comma-separated input, for example <code>--ids=1,2,3,4</code>.</li>
                <li><code>command_flags_print_usage(array $command): void</code> Prints usage text with all registered flags and defaults.</li>
                <li><code>Accepted formats</code> Use <code>--name=value</code>, <code>--name value</code>, or boolean switches like <code>--force</code>.</li>
                <li><code>validator</code> Build with <code>new ValidationRule('field')</code> and pass using <code>validator:</code> (for example <code>required()-&gt;string()-&gt;min(2)</code>).</li>
                <li><code>default_value</code> Example: <code>command_flag_array($command, '-p', 'My command', default_value: [1, 2, 3, 4])</code>.</li>
                <li><code>Validation errors</code> If validator rules fail, <code>Harbor\Command\CommandValueRequiredException</code> is thrown with validator error messages.</li>
            </ul>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Run Commands From Code</h2>
    <h3>Example</h3>
    <pre><code class="language-php">use Harbor\Helper;

use function Harbor\Command\command_run;

Helper::load_many('command');

$exit_code = command_run('users:import', ['--file=storage/users.csv', '--chunk=500']);

if (0 !== $exit_code) {
    // handle failure path
}</code></pre>
    <h3>What it does</h3>
    <p>Runs one command key from application code (for example API endpoints or internal workflows) without calling the CLI binary directly.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Helper API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>command_run(string $key, array $forwarded_arguments = [], ?string $working_directory = null, bool $debug_mode = false): int</code> Runs one command key and returns its process exit code.</li>
                <li><code>$forwarded_arguments</code> Values are forwarded as argv arguments to the command entry script.</li>
                <li><code>$working_directory</code> Optional site root path (directory containing <code>.router</code>). Defaults to current working directory.</li>
                <li><code>$debug_mode</code> Enables debug diagnostics from the command runner.</li>
                <li><code>Errors</code> Invalid keys, missing registry/entry files, disabled commands, and timeout failures throw <code>Harbor\CommandSystem\CommandException</code>.</li>
            </ul>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Manual Definitions</h2>
    <h3>Example</h3>
    <pre><code class="language-ini"># File: my-site/.commands
#include "./commands/core.commands"

&lt;command&gt;
    key: users:export
    entry: commands/users_export.php
    name: "Export Users"
    description: "Exports users to CSV"
    timeout_seconds: 90
    enabled: true
&lt;/command&gt;</code></pre>
    <pre><code class="language-ini"># File: my-site/commands/core.commands
&lt;command&gt;
    key: cache:warm
    entry: commands/cache_warm.php
    enabled: true
&lt;/command&gt;</code></pre>
    <pre><code class="language-php">&lt;?php

declare(strict_types=1);

// File: my-site/commands/users_export.php
use Harbor\Helper;
use function Harbor\Command\command_arguments;
use function Harbor\Command\command_flag_string;
use function Harbor\Command\command_init;
use function Harbor\Command\command_info;

require __DIR__."/../../vendor/autoload.php";
Helper::Command->load();

$command = command_init('users:export', $argc ?? 0, $argv ?? []);
$arguments = command_arguments();
$format = command_flag_string($command, '--format', 'Export format', default_value: 'csv');

command_info(sprintf('users:export format=%s args=%s', $format, json_encode($arguments)));</code></pre>
    <pre><code class="language-bash">cd my-site
../bin/harbor-command compile
../bin/harbor-command run users:export -- --format=csv</code></pre>
    <h3>What it does</h3>
    <p>Lets you split command definitions across files, compile them into a registry, and run keys normally.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Definition API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>Root file</code> Keep the main source at <code>my-site/.commands</code>.</li>
                <li><code>#include</code> Use <code>#include "./commands/core.commands"</code> to load other definition files.</li>
                <li><code>Relative include path</code> Resolved from the file that contains the <code>#include</code> line.</li>
                <li><code>Required fields</code> <code>key</code> and <code>entry</code> are required in every <code>&lt;command&gt;</code> block.</li>
                <li><code>Optional fields</code> <code>name</code>, <code>description</code>, <code>timeout_seconds</code>, <code>enabled</code>, <code>created_at</code>, <code>updated_at</code>.</li>
                <li><code>After manual edits</code> Run <code>../bin/harbor-command compile</code> to regenerate <code>commands/commands.php</code>.</li>
                <li><code>Validation</code> Nested command blocks, invalid field syntax, duplicate keys, invalid booleans, and circular includes fail compile.</li>
            </ul>
        </div>
    </details>
</section>

<section class="docs-section">
    <h2>Compile Definitions</h2>
    <h3>Example</h3>
    <pre><code class="language-bash">cd my-site
../bin/harbor-command compile
../bin/harbor-command compile .
../bin/harbor-command compile .commands
../bin/harbor-command compile /absolute/path/to/site/.commands</code></pre>
    <h3>What it does</h3>
    <p>Compiles command definitions into a PHP registry file at <code>commands/commands.php</code>.</p>
    <h3>API</h3>
    <details class="api-details">
        <summary class="api-summary">
            <span>Compile API</span>
            <span class="api-state"><span class="api-state-closed">Hidden - click to open</span><span class="api-state-open">Open</span></span>
        </summary>
        <div class="api-body">
            <ul class="api-method-list">
                <li><code>compile</code> Uses current directory <code>.commands</code> and writes <code>commands/commands.php</code>.</li>
                <li><code>compile &lt;directory&gt;</code> Reads <code>&lt;directory&gt;/.commands</code> and writes <code>&lt;directory&gt;/commands/commands.php</code>.</li>
                <li><code>compile &lt;path-to-.commands&gt;</code> Reads source file and writes sibling <code>commands/commands.php</code>.</li>
                <li><code>Invalid path argument</code> Must be a directory or a file path ending with <code>.commands</code>.</li>
            </ul>
        </div>
    </details>
</section>

<?php require __DIR__.'/../shared/footer.php'; ?>
