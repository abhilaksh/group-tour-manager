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

echo "<h2>Composer</h2>";
$composer = shell_exec('which composer 2>&1');
echo "<p>" . htmlspecialchars($composer) . "</p>";

echo "<h2>Node</h2>";
$node = shell_exec('which node 2>&1');
echo "<p>" . htmlspecialchars($node) . "</p>";

echo "<h2>Test JSON Response</h2>";
header('Content-Type: application/json');
echo json_encode(['test' => 'success', 'php_version' => PHP_VERSION]);
?>
