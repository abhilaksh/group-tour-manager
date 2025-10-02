<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Information</h1>";

echo "<h2>PHP Version</h2>";
echo "<p>" . PHP_VERSION . "</p>";

echo "<h2>Current Directory</h2>";
echo "<p>" . getcwd() . "</p>";

echo "<h2>Base Path</h2>";
$basePath = dirname(__DIR__);
echo "<p>" . $basePath . "</p>";

echo "<h2>Directory Exists?</h2>";
echo "<p>Storage: " . (is_dir($basePath . '/storage') ? 'YES' : 'NO') . "</p>";
echo "<p>Bootstrap: " . (is_dir($basePath . '/bootstrap') ? 'YES' : 'NO') . "</p>";
echo "<p>Vendor: " . (is_dir($basePath . '/vendor') ? 'YES' : 'NO') . "</p>";

echo "<h2>Writable?</h2>";
echo "<p>Storage: " . (is_writable($basePath . '/storage') ? 'YES' : 'NO') . "</p>";
echo "<p>Bootstrap/cache: " . (is_writable($basePath . '/bootstrap/cache') ? 'YES' : 'NO') . "</p>";

echo "<h2>Composer Location</h2>";
$composerWhich = shell_exec('which composer 2>&1');
echo "<p>which composer: " . htmlspecialchars($composerWhich ? $composerWhich : 'NOT FOUND') . "</p>";

$composerVersion = shell_exec('composer --version 2>&1');
echo "<p>composer --version: " . htmlspecialchars($composerVersion ? $composerVersion : 'NOT FOUND') . "</p>";

$composerPath = shell_exec('/usr/local/bin/composer --version 2>&1');
echo "<p>/usr/local/bin/composer: " . htmlspecialchars($composerPath ? $composerPath : 'NOT FOUND') . "</p>";

echo "<h2>Node Location</h2>";
$nodeWhich = shell_exec('which node 2>&1');
echo "<p>which node: " . htmlspecialchars($nodeWhich ? $nodeWhich : 'NOT FOUND') . "</p>";

$nodeVersion = shell_exec('node --version 2>&1');
echo "<p>node --version: " . htmlspecialchars($nodeVersion ? $nodeVersion : 'NOT FOUND') . "</p>";

echo "<h2>NPM Location</h2>";
$npmWhich = shell_exec('which npm 2>&1');
echo "<p>which npm: " . htmlspecialchars($npmWhich ? $npmWhich : 'NOT FOUND') . "</p>";

$npmVersion = shell_exec('npm --version 2>&1');
echo "<p>npm --version: " . htmlspecialchars($npmVersion ? $npmVersion : 'NOT FOUND') . "</p>";

echo "<h2>PATH</h2>";
$path = shell_exec('echo $PATH 2>&1');
echo "<p>" . htmlspecialchars($path) . "</p>";
?>
