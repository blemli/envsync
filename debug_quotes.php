<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\File;
use Blemli\Envsync\Commands\EnvsyncCommand;

// Create test files
file_put_contents('.env', 'VITE_APP_NAME="${APP_NAME}"' . "\n");
file_put_contents('.env.example', 'VITE_APP_NAME="${OLD_NAME}"' . "\n");

echo "=== Debug Quote Detection ===\n\n";

echo "📄 .env content:\n";
echo file_get_contents('.env');
echo "\n📄 .env.example content:\n";
echo file_get_contents('.env.example');

// Test the parsing
$command = new EnvsyncCommand();
$reflection = new \ReflectionClass($command);
$parseMethod = $reflection->getMethod('parseEnvFileWithStructure');
$parseMethod->setAccessible(true);

$sourceData = $parseMethod->invoke($command, '.env');
$targetData = $parseMethod->invoke($command, '.env.example');

echo "\n🔍 Source structure:\n";
foreach ($sourceData['structure'] as $lineNum => $lineData) {
    if ($lineData['type'] === 'env_var') {
        echo "Line {$lineNum}: '{$lineData['original']}'\n";
        echo "  Key: {$lineData['key']}\n";
        echo "  Value: {$lineData['value']}\n";
        
        // Test quote detection
        $originalLine = $lineData['original'];
        $hasQuotes = preg_match('/=\s*"/', $originalLine) || preg_match("/=\s*'/", $originalLine);
        echo "  Has quotes: " . ($hasQuotes ? 'YES' : 'NO') . "\n";
    }
}

echo "\n🔍 Target structure:\n";
foreach ($targetData['structure'] as $lineNum => $lineData) {
    if ($lineData['type'] === 'env_var') {
        echo "Line {$lineNum}: '{$lineData['original']}'\n";
        echo "  Key: {$lineData['key']}\n";
        echo "  Value: {$lineData['value']}\n";
    }
}

// Test sync method
$syncMethod = $reflection->getMethod('syncValueToTarget');
$syncMethod->setAccessible(true);

echo "\n🚀 Testing sync...\n";
$syncMethod->invoke($command, '.env.example', 'VITE_APP_NAME', '${APP_NAME}', false);

echo "\n📄 .env.example after sync:\n";
echo file_get_contents('.env.example');
