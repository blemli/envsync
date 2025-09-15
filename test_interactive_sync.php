<?php

// Create test files to demonstrate the new interactive sync functionality

// Create a source .env file
file_put_contents('.env', "APP_NAME=MyApp\nAPP_ENV=production\nDB_HOST=localhost\nDB_PORT=3306\nAPI_KEY=secret123\n");

// Create a target .env.example file with some differing values
file_put_contents('.env.example', "APP_NAME=ExampleApp\nAPP_ENV=local\nDB_HOST=127.0.0.1\nDB_PORT=3306\nAPI_KEY=your_api_key_here\n");

echo "Test files created:\n";
echo "\n.env contents:\n";
echo file_get_contents('.env');
echo "\n.env.example contents:\n";
echo file_get_contents('.env.example');

echo "\nNow run: php artisan env:sync --path=.env.example\n";
echo "This will show interactive prompts for each differing value.\n";
echo "\nOr try: php artisan env:sync --path=.env.example --auto-sync\n";
echo "This will automatically sync all differing values.\n";
