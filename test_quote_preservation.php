<?php

echo "=== Testing Quote Preservation Fix ===\n\n";

// Clean up any existing test files
if (file_exists('.env')) unlink('.env');
if (file_exists('.env.example')) unlink('.env.example');

// Create source .env file with quoted values
file_put_contents('.env', 
'APP_NAME="My Application"
VITE_APP_NAME="${APP_NAME}"
DATABASE_URL="mysql://user:pass@localhost/db"
SIMPLE_VALUE=unquoted
SPACED_VALUE="Value with spaces"
DOLLAR_VALUE="${OTHER_VAR}/path"
');

// Create target .env.example with different values but should preserve quoting
file_put_contents('.env.example',
'APP_NAME="Example App"
VITE_APP_NAME="${OLD_APP_NAME}"
DATABASE_URL="mysql://example:pass@localhost/example"
SIMPLE_VALUE=different
SPACED_VALUE="Different spaced value"
DOLLAR_VALUE="${DIFFERENT_VAR}/path"
');

echo "📄 Created .env (source with quotes):\n";
echo file_get_contents('.env');
echo "\n📄 Created .env.example (target):\n";
echo file_get_contents('.env.example');

echo "\n🔍 Expected behavior after sync:\n";
echo "- APP_NAME should remain quoted: \"My Application\"\n";
echo "- VITE_APP_NAME should remain quoted: \"\${APP_NAME}\"\n";
echo "- DATABASE_URL should remain quoted\n";
echo "- SIMPLE_VALUE should remain unquoted\n";
echo "- SPACED_VALUE should remain quoted (has spaces)\n";
echo "- DOLLAR_VALUE should remain quoted (has \$ variable)\n\n";

echo "🚀 Run: php artisan env:sync --path=.env.example --auto-sync\n";
echo "✅ All quoted values should preserve their quotes!\n";
echo "✅ VITE_APP_NAME=\"\${APP_NAME}\" should NOT become VITE_APP_NAME=\${APP_NAME}\n";
