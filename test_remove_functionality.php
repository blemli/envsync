<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\File;

// Create test files
echo "Creating test files...\n";

// Create source file (.env) with extra keys
$sourceContent = "APP_NAME=TestApp
APP_ENV=local
APP_KEY=base64:test123
APP_DEBUG=true
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
EXTRA_KEY_1=value1
EXTRA_KEY_2=value2
MAIL_MAILER=smtp";

File::put('.env', $sourceContent);

// Create target file (.env.example) with fewer keys
$targetContent = "APP_NAME=
APP_ENV=
APP_KEY=
APP_DEBUG=
DB_CONNECTION=
DB_HOST=";

File::put('.env.example', $targetContent);

echo "Source file (.env) created with " . count(explode("\n", trim($sourceContent))) . " keys\n";
echo "Target file (.env.example) created with " . count(explode("\n", trim($targetContent))) . " keys\n";

echo "\nSource file contents:\n";
echo File::get('.env');

echo "\nTarget file contents:\n";
echo File::get('.env.example');

echo "\n" . str_repeat("=", 50) . "\n";
echo "TEST 1: Interactive remove (should show confirmation prompts)\n";
echo "Run: php artisan env:sync --path=.env.example --remove\n";
echo "Expected: Should ask for confirmation to remove EXTRA_KEY_1, EXTRA_KEY_2, MAIL_MAILER\n";

echo "\n" . str_repeat("=", 50) . "\n";
echo "TEST 2: Force remove with version control check\n";
echo "Run: php artisan env:sync --path=.env.example --remove --force\n";
echo "Expected: Should check if .env is version controlled and act accordingly\n";

echo "\n" . str_repeat("=", 50) . "\n";
echo "Files created successfully. You can now test the --remove functionality.\n";
echo "Remember to backup your original .env file if needed!\n";
