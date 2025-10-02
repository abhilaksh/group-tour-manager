<?php
/**
 * Group Tour Manager - Web Installer
 * A beautiful, step-by-step installation wizard
 */

error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to avoid breaking JSON
ini_set('log_errors', 1);

session_start();

// Security: Disable after installation
if (file_exists(__DIR__ . '/../.installed')) {
    die('Installation has already been completed. Delete the .installed file to run the installer again.');
}

define('BASE_PATH', dirname(__DIR__));

// Helper functions
function runCommand($command, &$output = null) {
    $fullCommand = "cd " . BASE_PATH . " && $command 2>&1";
    exec($fullCommand, $output, $returnCode);
    return $returnCode === 0;
}

function installComposer(&$output = null) {
    $output = [];

    // Download Composer installer
    $success1 = runCommand('php -r "copy(\'https://getcomposer.org/installer\', \'composer-setup.php\');"', $output);
    $output[] = "Download installer: " . ($success1 ? 'SUCCESS' : 'FAILED');

    if (!$success1 || !file_exists(BASE_PATH . '/composer-setup.php')) {
        $output[] = "Composer setup file not created";
        return false;
    }

    // Run Composer installer
    $success2 = runCommand('php composer-setup.php --install-dir=' . BASE_PATH . ' --filename=composer.phar', $output);
    $output[] = "Run installer: " . ($success2 ? 'SUCCESS' : 'FAILED');

    // Clean up installer
    runCommand('rm composer-setup.php', $output);

    // Check if composer.phar exists
    if (file_exists(BASE_PATH . '/composer.phar')) {
        $output[] = "composer.phar created successfully at " . BASE_PATH . '/composer.phar';
        return true;
    }

    $output[] = "composer.phar NOT found at " . BASE_PATH . '/composer.phar';
    return false;
}

function getComposerCommand() {
    // Check if composer is in PATH
    $which = trim(shell_exec('which composer 2>&1'));
    if (!empty($which) && strpos($which, 'not found') === false) {
        return 'composer';
    }

    // Check if we have composer.phar locally
    if (file_exists(BASE_PATH . '/composer.phar')) {
        return 'php ' . BASE_PATH . '/composer.phar';
    }

    // Default fallback
    return 'composer';
}

function checkRequirements() {
    // Check for Composer in PATH or as local .phar
    $composerWhich = trim(shell_exec('which composer 2>&1'));
    $composerInstalled = (strpos($composerWhich, 'not found') === false && !empty($composerWhich))
                         || file_exists(BASE_PATH . '/composer.phar');

    // Check Node.js
    $nodeWhich = trim(shell_exec('which node 2>&1'));
    $nodeInstalled = strpos($nodeWhich, 'not found') === false && !empty($nodeWhich);

    // Check NPM
    $npmWhich = trim(shell_exec('which npm 2>&1'));
    $npmInstalled = strpos($npmWhich, 'not found') === false && !empty($npmWhich);

    // Check Git
    $gitWhich = trim(shell_exec('which git 2>&1'));
    $gitInstalled = strpos($gitWhich, 'not found') === false && !empty($gitWhich);

    $requirements = [
        'PHP Version >= 8.3' => version_compare(PHP_VERSION, '8.3.0', '>='),
        'Composer' => $composerInstalled,
        'Node.js' => $nodeInstalled,
        'NPM' => $npmInstalled,
        'Git' => $gitInstalled,
        'PHP Extension: PDO' => extension_loaded('pdo'),
        'PHP Extension: MySQL' => extension_loaded('pdo_mysql'),
        'PHP Extension: OpenSSL' => extension_loaded('openssl'),
        'PHP Extension: Mbstring' => extension_loaded('mbstring'),
        'PHP Extension: Tokenizer' => extension_loaded('tokenizer'),
        'PHP Extension: XML' => extension_loaded('xml'),
        'PHP Extension: Ctype' => extension_loaded('ctype'),
        'PHP Extension: JSON' => extension_loaded('json'),
        'Directory: storage/' => is_dir(BASE_PATH . '/storage'),
        'Directory: bootstrap/' => is_dir(BASE_PATH . '/bootstrap'),
        'Writable: storage/' => is_writable(BASE_PATH . '/storage'),
        'Writable: bootstrap/cache/' => is_writable(BASE_PATH . '/bootstrap/cache'),
    ];

    return $requirements;
}

function testDatabaseConnection($host, $port, $database, $username, $password) {
    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$database";
        $pdo = new PDO($dsn, $username, $password);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        switch ($_POST['action']) {
            case 'check_requirements':
                $requirements = checkRequirements();
                echo json_encode(['success' => true, 'requirements' => $requirements]);
                exit;

            case 'install_composer':
                $output = [];
                $success = installComposer($output);
                echo json_encode(['success' => $success, 'output' => implode("\n", $output)]);
                exit;

        case 'test_database':
            $host = $_POST['db_host'] ?? '127.0.0.1';
            $port = $_POST['db_port'] ?? '3306';
            $database = $_POST['db_database'] ?? '';
            $username = $_POST['db_username'] ?? '';
            $password = $_POST['db_password'] ?? '';

            $success = testDatabaseConnection($host, $port, $database, $username, $password);
            echo json_encode(['success' => $success]);
            exit;

        case 'save_env':
            $envContent = "APP_NAME=\"{$_POST['app_name']}\"\n";
            $envContent .= "APP_ENV=production\n";
            $envContent .= "APP_KEY=\n";
            $envContent .= "APP_DEBUG=false\n";
            $envContent .= "APP_URL={$_POST['app_url']}\n\n";

            $envContent .= "DB_CONNECTION=mysql\n";
            $envContent .= "DB_HOST={$_POST['db_host']}\n";
            $envContent .= "DB_PORT={$_POST['db_port']}\n";
            $envContent .= "DB_DATABASE={$_POST['db_database']}\n";
            $envContent .= "DB_USERNAME={$_POST['db_username']}\n";
            $envContent .= "DB_PASSWORD={$_POST['db_password']}\n\n";

            $envContent .= "BROADCAST_DRIVER=log\n";
            $envContent .= "CACHE_DRIVER=file\n";
            $envContent .= "FILESYSTEM_DISK=local\n";
            $envContent .= "QUEUE_CONNECTION=sync\n";
            $envContent .= "SESSION_DRIVER=file\n";
            $envContent .= "SESSION_LIFETIME=120\n";

            $success = file_put_contents(BASE_PATH . '/.env', $envContent) !== false;
            echo json_encode(['success' => $success]);
            exit;

        case 'install_backend':
            $output = [];
            $composer = getComposerCommand();
            $commands = [
                $composer . ' install --no-dev --optimize-autoloader --no-interaction',
                'php artisan key:generate --force',
                'php artisan storage:link',
            ];

            foreach ($commands as $command) {
                if (!runCommand($command, $output)) {
                    echo json_encode(['success' => false, 'output' => implode("\n", $output)]);
                    exit;
                }
            }

            echo json_encode(['success' => true, 'output' => implode("\n", $output)]);
            exit;

        case 'run_migrations':
            $output = [];
            $seed = $_POST['seed'] === 'true';

            $success = runCommand('php artisan migrate --force', $output);

            if ($success && $seed) {
                $success = runCommand('php artisan db:seed', $output);
            }

            echo json_encode(['success' => $success, 'output' => implode("\n", $output)]);
            exit;

        case 'install_frontend':
            $output = [];
            $commands = [
                'cd client && npm install',
                'cd client && npm run build',
            ];

            foreach ($commands as $command) {
                if (!runCommand($command, $output)) {
                    echo json_encode(['success' => false, 'output' => implode("\n", $output)]);
                    exit;
                }
            }

            echo json_encode(['success' => true, 'output' => implode("\n", $output)]);
            exit;

        case 'finalize':
            $output = [];
            $commands = [
                'php artisan config:cache',
                'php artisan route:cache',
                'php artisan view:cache',
                'php artisan optimize',
                'chmod -R 755 storage bootstrap/cache',
            ];

            foreach ($commands as $command) {
                runCommand($command, $output);
            }

            // Create installation marker
            file_put_contents(BASE_PATH . '/.installed', date('Y-m-d H:i:s'));

            echo json_encode(['success' => true, 'output' => implode("\n", $output)]);
            exit;

        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        exit;
    } catch (Error $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        exit;
    }
}

// HTML Interface
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Tour Manager - Installation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .installer {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 700px;
            width: 100%;
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .progress {
            display: flex;
            padding: 30px 40px;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
        }

        .progress-step {
            flex: 1;
            text-align: center;
            position: relative;
        }

        .progress-step::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: #e5e7eb;
            z-index: 0;
        }

        .progress-step:first-child::before { display: none; }

        .progress-number {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #6b7280;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            position: relative;
            z-index: 1;
            margin-bottom: 8px;
        }

        .progress-step.active .progress-number {
            background: #667eea;
            color: white;
        }

        .progress-step.complete .progress-number {
            background: #10b981;
            color: white;
        }

        .progress-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
        }

        .progress-step.active .progress-label {
            color: #667eea;
        }

        .content {
            padding: 40px;
        }

        .step {
            display: none;
        }

        .step.active {
            display: block;
        }

        .step h2 {
            font-size: 22px;
            color: #111827;
            margin-bottom: 8px;
        }

        .step p {
            color: #6b7280;
            margin-bottom: 24px;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
            font-size: 14px;
        }

        input[type="text"],
        input[type="password"],
        input[type="number"] {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .requirements-list {
            list-style: none;
        }

        .requirement {
            padding: 12px;
            margin-bottom: 8px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            font-size: 14px;
        }

        .requirement.pass {
            background: #d1fae5;
            color: #065f46;
        }

        .requirement.fail {
            background: #fee2e2;
            color: #991b1b;
        }

        .requirement::before {
            content: '✓';
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 12px;
            text-align: center;
            line-height: 20px;
            font-weight: bold;
        }

        .requirement.pass::before {
            background: #10b981;
            color: white;
        }

        .requirement.fail::before {
            content: '✗';
            background: #ef4444;
            color: white;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:disabled {
            background: #d1d5db;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
            margin-right: 12px;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
        }

        .actions {
            margin-top: 32px;
            display: flex;
            justify-content: space-between;
        }

        .output {
            background: #1f2937;
            color: #10b981;
            padding: 16px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
            white-space: pre-wrap;
            margin-top: 16px;
            display: none;
        }

        .output.show {
            display: block;
        }

        .spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-top: 16px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="installer">
        <div class="header">
            <h1>Group Tour Manager</h1>
            <p>Installation Wizard</p>
        </div>

        <div class="progress">
            <div class="progress-step active" data-step="1">
                <div class="progress-number">1</div>
                <div class="progress-label">Requirements</div>
            </div>
            <div class="progress-step" data-step="2">
                <div class="progress-number">2</div>
                <div class="progress-label">Configuration</div>
            </div>
            <div class="progress-step" data-step="3">
                <div class="progress-number">3</div>
                <div class="progress-label">Installation</div>
            </div>
            <div class="progress-step" data-step="4">
                <div class="progress-number">4</div>
                <div class="progress-label">Complete</div>
            </div>
        </div>

        <div class="content">
            <!-- Step 1: Requirements -->
            <div class="step active" id="step-1">
                <h2>System Requirements</h2>
                <p>Checking if your server meets all requirements...</p>

                <ul class="requirements-list" id="requirements-list">
                    <li class="requirement"><span class="spinner"></span> Checking requirements...</li>
                </ul>

                <div id="missing-tools"></div>

                <div class="actions">
                    <div></div>
                    <button class="btn btn-primary" id="btn-next-1" disabled>Next</button>
                </div>
            </div>

            <!-- Step 2: Configuration -->
            <div class="step" id="step-2">
                <h2>Application Configuration</h2>
                <p>Configure your application and database settings.</p>

                <div class="form-group">
                    <label>Application Name</label>
                    <input type="text" id="app_name" value="Group Tour Manager" required>
                </div>

                <div class="form-group">
                    <label>Application URL</label>
                    <input type="text" id="app_url" value="<?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>" required>
                </div>

                <h3 style="margin-top: 32px; margin-bottom: 16px; font-size: 18px;">Database Configuration</h3>

                <div class="form-group">
                    <label>Database Host</label>
                    <input type="text" id="db_host" value="127.0.0.1" required>
                </div>

                <div class="form-group">
                    <label>Database Port</label>
                    <input type="number" id="db_port" value="3306" required>
                </div>

                <div class="form-group">
                    <label>Database Name</label>
                    <input type="text" id="db_database" required>
                </div>

                <div class="form-group">
                    <label>Database Username</label>
                    <input type="text" id="db_username" required>
                </div>

                <div class="form-group">
                    <label>Database Password</label>
                    <input type="password" id="db_password">
                </div>

                <div id="db-test-result"></div>

                <div class="actions">
                    <button class="btn btn-secondary" onclick="goToStep(1)">Back</button>
                    <button class="btn btn-primary" id="btn-next-2">Test & Continue</button>
                </div>
            </div>

            <!-- Step 3: Installation -->
            <div class="step" id="step-3">
                <h2>Installation Progress</h2>
                <p>Installing your application. This may take a few minutes...</p>

                <div id="install-status"></div>
                <div class="output" id="install-output"></div>

                <div class="checkbox-group">
                    <input type="checkbox" id="run_seed" checked>
                    <label for="run_seed" style="margin: 0;">Seed database with sample data</label>
                </div>

                <div class="actions">
                    <button class="btn btn-secondary" onclick="goToStep(2)" id="btn-back-3">Back</button>
                    <button class="btn btn-primary" id="btn-install">Start Installation</button>
                </div>
            </div>

            <!-- Step 4: Complete -->
            <div class="step" id="step-4">
                <h2>🎉 Installation Complete!</h2>
                <p>Your Group Tour Manager application has been successfully installed.</p>

                <div class="alert alert-success">
                    <strong>Success!</strong> Your application is ready to use.
                </div>

                <h3 style="margin-top: 32px; margin-bottom: 16px; font-size: 18px;">Next Steps:</h3>
                <ul style="margin-left: 20px; line-height: 2; color: #374151;">
                    <li>Delete or secure the <code>install.php</code> file</li>
                    <li>Configure your GitHub webhook for automatic deployments</li>
                    <li>Set up queue workers in RunCloud (optional)</li>
                    <li>Visit your application and start managing tours!</li>
                </ul>

                <div class="actions" style="margin-top: 40px;">
                    <div></div>
                    <a href="/" class="btn btn-primary" style="text-decoration: none; display: inline-block;">Launch Application</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentStep = 1;

        // Check requirements on load
        window.addEventListener('DOMContentLoaded', checkRequirements);

        async function checkRequirements() {
            const response = await fetch('install.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=check_requirements'
            });

            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                document.getElementById('requirements-list').innerHTML = `
                    <div class="alert alert-error">
                        <strong>Error:</strong> Server returned invalid response.<br>
                        <pre style="margin-top: 10px; font-size: 11px; max-height: 200px; overflow: auto;">${text.substring(0, 1000)}</pre>
                    </div>
                `;
                return;
            }
            const list = document.getElementById('requirements-list');
            list.innerHTML = '';

            let allPass = true;
            let missingComposer = false;

            for (const [name, pass] of Object.entries(data.requirements)) {
                const li = document.createElement('li');
                li.className = `requirement ${pass ? 'pass' : 'fail'}`;
                li.textContent = name;
                list.appendChild(li);

                if (!pass) {
                    allPass = false;
                    if (name === 'Composer') missingComposer = true;
                }
            }

            const missingToolsDiv = document.getElementById('missing-tools');
            if (missingComposer) {
                missingToolsDiv.innerHTML = `
                    <div class="alert alert-error" style="margin-top: 20px;">
                        <strong>Composer is not installed.</strong>
                        <button class="btn btn-primary" onclick="installComposer()" style="margin-left: 12px; padding: 8px 16px;">
                            Install Composer Now
                        </button>
                    </div>
                `;
            } else {
                missingToolsDiv.innerHTML = '';
            }

            document.getElementById('btn-next-1').disabled = !allPass;
        }

        async function installComposer() {
            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Installing Composer...';

            const response = await fetch('install.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=install_composer'
            });

            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON:', text);
                btn.disabled = false;
                btn.textContent = 'Install Failed - Check Console';
                alert('Error: ' + text.substring(0, 500));
                return;
            }

            if (data.success) {
                btn.innerHTML = '✓ Installed';
                if (data.output) {
                    console.log('Composer install output:', data.output);
                }
                setTimeout(() => {
                    checkRequirements();
                }, 1500);
            } else {
                btn.disabled = false;
                btn.textContent = 'Install Failed - Try Again';
                if (data.output) {
                    alert('Installation failed:\n\n' + data.output);
                }
                console.error('Install failed:', data);
            }
        }

        function goToStep(step) {
            document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.progress-step').forEach(s => s.classList.remove('active', 'complete'));

            document.getElementById(`step-${step}`).classList.add('active');

            for (let i = 1; i <= 4; i++) {
                const progressStep = document.querySelector(`.progress-step[data-step="${i}"]`);
                if (i < step) {
                    progressStep.classList.add('complete');
                } else if (i === step) {
                    progressStep.classList.add('active');
                }
            }

            currentStep = step;
        }

        document.getElementById('btn-next-1').addEventListener('click', () => goToStep(2));

        document.getElementById('btn-next-2').addEventListener('click', async () => {
            const btn = document.getElementById('btn-next-2');
            const resultDiv = document.getElementById('db-test-result');

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Testing connection...';

            const formData = new FormData();
            formData.append('action', 'test_database');
            formData.append('db_host', document.getElementById('db_host').value);
            formData.append('db_port', document.getElementById('db_port').value);
            formData.append('db_database', document.getElementById('db_database').value);
            formData.append('db_username', document.getElementById('db_username').value);
            formData.append('db_password', document.getElementById('db_password').value);

            const response = await fetch('install.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                resultDiv.innerHTML = '<div class="alert alert-success">Database connection successful!</div>';

                // Save .env
                const envData = new FormData();
                envData.append('action', 'save_env');
                envData.append('app_name', document.getElementById('app_name').value);
                envData.append('app_url', document.getElementById('app_url').value);
                envData.append('db_host', document.getElementById('db_host').value);
                envData.append('db_port', document.getElementById('db_port').value);
                envData.append('db_database', document.getElementById('db_database').value);
                envData.append('db_username', document.getElementById('db_username').value);
                envData.append('db_password', document.getElementById('db_password').value);

                await fetch('install.php', { method: 'POST', body: envData });

                setTimeout(() => goToStep(3), 1000);
            } else {
                resultDiv.innerHTML = '<div class="alert alert-error">Database connection failed. Please check your credentials.</div>';
                btn.disabled = false;
                btn.textContent = 'Test & Continue';
            }
        });

        document.getElementById('btn-install').addEventListener('click', async () => {
            const btn = document.getElementById('btn-install');
            const backBtn = document.getElementById('btn-back-3');
            const statusDiv = document.getElementById('install-status');
            const outputDiv = document.getElementById('install-output');

            btn.disabled = true;
            backBtn.disabled = true;
            outputDiv.classList.add('show');

            const steps = [
                { action: 'install_backend', label: 'Installing backend dependencies' },
                { action: 'run_migrations', label: 'Running database migrations' },
                { action: 'install_frontend', label: 'Building frontend' },
                { action: 'finalize', label: 'Finalizing installation' }
            ];

            for (const step of steps) {
                statusDiv.innerHTML = `<div class="alert alert-success"><span class="spinner"></span> ${step.label}...</div>`;

                const formData = new FormData();
                formData.append('action', step.action);
                if (step.action === 'run_migrations') {
                    formData.append('seed', document.getElementById('run_seed').checked);
                }

                const response = await fetch('install.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.output) {
                    outputDiv.textContent += data.output + '\n\n';
                    outputDiv.scrollTop = outputDiv.scrollHeight;
                }

                if (!data.success) {
                    statusDiv.innerHTML = `<div class="alert alert-error">Installation failed at: ${step.label}</div>`;
                    btn.disabled = false;
                    backBtn.disabled = false;
                    return;
                }
            }

            statusDiv.innerHTML = '<div class="alert alert-success">Installation completed successfully!</div>';
            setTimeout(() => goToStep(4), 1500);
        });
    </script>
</body>
</html>
