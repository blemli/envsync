<?php

// Create test files to demonstrate the #ENVIGNORE functionality

// Create a source .env file
file_put_contents('.env', "APP_NAME=MyApp\nAPP_ENV=production\nDB_HOST=localhost\nDB_PORT=3306\nAPI_KEY=secret123\nDEBUG_MODE=true\n");

// Create a target .env.example file with some differing values and one ignored line
file_put_contents('.env.example', "APP_NAME=ExampleApp\nAPP_ENV=local\nDB_HOST=127.0.0.1 #ENVIGNORE\nDB_PORT=3306\nAPI_KEY=your_api_key_here\nDEBUG_MODE=false\n");

echo "Test files created to demonstrate #ENVIGNORE functionality:\n";
echo "\n.env contents:\n";
echo file_get_contents('.env');
echo "\n.env.example contents (note the #ENVIGNORE comment):\n";
echo file_get_contents('.env.example');

echo "\nWhen you run: php artisan env:sync --path=.env.example\n";
echo "The DB_HOST line will be ignored because it has #ENVIGNORE\n";
echo "Only APP_NAME, APP_ENV, API_KEY, and DEBUG_MODE will be checked for differences.\n";

echo "\nExpected behavior:\n";
echo "- DB_HOST difference will be ignored (has #ENVIGNORE)\n";
echo "- APP_NAME: MyApp vs ExampleApp (will prompt)\n";
echo "- APP_ENV: production vs local (will prompt)\n";
echo "- API_KEY: secret123 vs your_api_key_here (will prompt)\n";
echo "- DEBUG_MODE: true vs false (will prompt)\n";
