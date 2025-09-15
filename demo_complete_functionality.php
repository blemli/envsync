<?php

echo "=== EnvSync Enhanced Interactive Demo ===\n\n";

// Clean up any existing demo files
$demoFiles = ['.env', '.env.example', '.env.production'];
foreach ($demoFiles as $file) {
    if (file_exists($file)) {
        unlink($file);
    }
}

// Create comprehensive demo files
echo "Creating demo files...\n";

// Source .env file
file_put_contents('.env', 
"# Application Configuration
APP_NAME=MyProductionApp
APP_ENV=production
APP_DEBUG=false

# Database Configuration  
DB_CONNECTION=mysql
DB_HOST=prod-server.com
DB_PORT=3306
DB_DATABASE=production_db
DB_USERNAME=prod_user
DB_PASSWORD=super_secret_password

# API Configuration
API_KEY=live_api_key_12345
API_URL=https://api.production.com

# Cache Configuration
CACHE_DRIVER=redis
REDIS_HOST=redis.production.com
");

// Target .env.example file with mixed scenarios
file_put_contents('.env.example',
"# Application Configuration
APP_NAME=ExampleApp
APP_ENV=local
APP_DEBUG=true

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=localhost #ENVIGNORE
DB_PORT=3306
DB_DATABASE=example_db
DB_USERNAME=your_username
DB_PASSWORD=your_password

# API Configuration  
API_KEY=your_api_key_here
API_URL=https://api.example.com

# Missing in source - will prompt for removal
MAIL_DRIVER=smtp
MAIL_HOST=smtp.example.com
");

echo "\n=== Demo Files Created ===\n";
echo "\n📄 .env (source file):\n";
echo file_get_contents('.env');
echo "\n📄 .env.example (target file with #ENVIGNORE):\n";
echo file_get_contents('.env.example');

echo "\n=== Available Commands to Test ===\n\n";

echo "1️⃣  Interactive Mode (default):\n";
echo "   php artisan env:sync --path=.env.example\n";
echo "   → Shows prompts for each differing value\n";
echo "   → DB_HOST will be ignored (has #ENVIGNORE)\n";
echo "   → Will prompt for: APP_NAME, APP_ENV, APP_DEBUG, DB_DATABASE, DB_USERNAME, DB_PASSWORD, API_KEY, API_URL\n";
echo "   → Will prompt to remove: MAIL_DRIVER, MAIL_HOST\n";
echo "   → Will prompt to add: CACHE_DRIVER, REDIS_HOST\n\n";

echo "2️⃣  Auto-Sync Mode:\n";
echo "   php artisan env:sync --path=.env.example --auto-sync\n";
echo "   → Automatically syncs all differing values\n";
echo "   → Still respects #ENVIGNORE comments\n\n";

echo "3️⃣  Force Mode:\n";
echo "   php artisan env:sync --path=.env.example --force\n";
echo "   → Skips all prompts and applies all changes\n\n";

echo "4️⃣  Test #ENVIGNORE functionality:\n";
echo "   → Notice how DB_HOST=localhost has #ENVIGNORE\n";
echo "   → This line will be completely ignored in sync operations\n";
echo "   → Even though .env has DB_HOST=prod-server.com\n\n";

echo "5️⃣  Test 'Forever Ignore' option:\n";
echo "   → Run interactive mode and choose 'Forever ignore' for any key\n";
echo "   → This will add #ENVIGNORE to that line\n";
echo "   → Future syncs will skip that key\n\n";

echo "=== Key Features Demonstrated ===\n";
echo "✅ Per-key interactive prompts\n";
echo "✅ Sync from source to target\n";
echo "✅ Ignore once (skip this time)\n";
echo "✅ Forever ignore (#ENVIGNORE comments)\n";
echo "✅ Structure preservation (comments, formatting)\n";
echo "✅ Auto-sync mode\n";
echo "✅ Force mode\n";
echo "✅ Missing/extra key handling\n";
echo "✅ Version control awareness\n\n";

echo "🚀 Ready to test! Run any of the commands above.\n";
