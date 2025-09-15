<?php

echo "=== Testing #ENVIGNORE Fix ===\n\n";

// Clean up any existing test files
if (file_exists('.env')) unlink('.env');
if (file_exists('.env.production')) unlink('.env.production');

// Create source .env file
file_put_contents('.env', 
"APP_NAME=MyApp
DB_HOST=localhost
DB_PORT=3306
API_KEY=secret123
CACHE_DRIVER=redis
");

// Create target .env.production with some ignored entries
file_put_contents('.env.production',
"APP_NAME=ProductionApp
DB_HOST=prod-server.com #ENVIGNORE
DB_PORT=3306
API_KEY=prod_secret
MAIL_DRIVER=smtp
");

echo "📄 Created .env (source):\n";
echo file_get_contents('.env');
echo "\n📄 Created .env.production (target with #ENVIGNORE):\n";
echo file_get_contents('.env.production');

echo "\n🔍 Expected behavior:\n";
echo "- DB_HOST should be shown as 'permanently ignored' (not missing)\n";
echo "- APP_NAME and API_KEY should show as differing values\n";
echo "- CACHE_DRIVER should show as missing in target\n";
echo "- MAIL_DRIVER should show as extra in target\n\n";

echo "🚀 Run: php artisan env:sync --path=.env.production\n";
echo "✅ DB_HOST should NOT appear in the 'missing' list anymore!\n";
