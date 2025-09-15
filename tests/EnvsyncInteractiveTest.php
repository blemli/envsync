<?php

use Illuminate\Support\Facades\File;
use Blemli\Envsync\Commands\EnvsyncCommand;

beforeEach(function () {
    // Clean up any existing test files
    $files = ['.env.test', '.env.example.test', '.env.interactive.test', '.env.ignore.test'];
    foreach ($files as $file) {
        if (File::exists($file)) {
            File::delete($file);
        }
    }
});

afterEach(function () {
    // Clean up test files after each test
    $files = ['.env.test', '.env.example.test', '.env.interactive.test', '.env.ignore.test'];
    foreach ($files as $file) {
        if (File::exists($file)) {
            File::delete($file);
        }
    }
});

it('can parse files with #ENVIGNORE comments', function () {
    $command = new EnvsyncCommand();
    $reflection = new \ReflectionClass($command);
    $method = $reflection->getMethod('parseEnvFile');
    $method->setAccessible(true);

    // Create test file with #ENVIGNORE
    $testContent = "APP_NAME=TestApp\nDB_HOST=localhost #ENVIGNORE\nAPI_KEY=secret\n";
    File::put('.env.ignore.test', $testContent);

    $result = $method->invoke($command, '.env.ignore.test');

    // Should exclude the line with #ENVIGNORE
    expect($result)->toBe([
        'APP_NAME' => 'TestApp',
        'API_KEY' => 'secret'
    ]);
    expect($result)->not->toHaveKey('DB_HOST');
});

it('can parse files with structured data including ignored lines', function () {
    $command = new EnvsyncCommand();
    $reflection = new \ReflectionClass($command);
    $method = $reflection->getMethod('parseEnvFileWithStructure');
    $method->setAccessible(true);

    // Create test file with mixed content
    $testContent = "# Comment\nAPP_NAME=TestApp\nDB_HOST=localhost #ENVIGNORE\n\nAPI_KEY=secret\n";
    File::put('.env.ignore.test', $testContent);

    $result = $method->invoke($command, '.env.ignore.test');

    expect($result['entries'])->toBe([
        'APP_NAME' => 'TestApp',
        'API_KEY' => 'secret'
    ]);

    // Check structure preservation
    expect($result['structure'])->toHaveCount(5); // 5 lines total
    expect($result['structure'][0]['type'])->toBe('comment');
    expect($result['structure'][1]['type'])->toBe('env_var');
    expect($result['structure'][1]['key'])->toBe('APP_NAME');
    expect($result['structure'][2]['type'])->toBe('env_var');
    expect($result['structure'][2]['key'])->toBe('DB_HOST');
    expect($result['structure'][2]['ignored'])->toBe(true);
    expect($result['structure'][3]['type'])->toBe('empty');
    expect($result['structure'][4]['type'])->toBe('env_var');
    expect($result['structure'][4]['key'])->toBe('API_KEY');
});

it('handles auto-sync option correctly', function () {
    // Create source file
    File::put('.env', "APP_NAME=MyApp\nDB_HOST=localhost\n");
    
    // Create target file with different values
    File::put('.env.interactive.test', "APP_NAME=ExampleApp\nDB_HOST=127.0.0.1\n");

    $this->artisan('env:sync --path=.env.interactive.test --auto-sync')
        ->expectsOutput('Syncing \'.env\' with \'.env.interactive.test\'')
        ->expectsOutput('Differing values detected:')
        ->expectsOutput('Automatically syncing (--auto-sync enabled)')
        ->expectsOutput('✓ Synced \'APP_NAME\' from .env to .env.interactive.test')
        ->expectsOutput('✓ Synced \'DB_HOST\' from .env to .env.interactive.test')
        ->expectsOutput('Successfully synced \'.env.interactive.test\' with \'.env\'')
        ->assertExitCode(0);

    // Verify the target file was updated
    $targetContent = File::get('.env.interactive.test');
    expect($targetContent)->toContain('APP_NAME=MyApp');
    expect($targetContent)->toContain('DB_HOST=localhost');
});

it('preserves file structure when syncing values', function () {
    $command = new EnvsyncCommand();
    $reflection = new \ReflectionClass($command);
    $syncMethod = $reflection->getMethod('syncValueToTarget');
    $syncMethod->setAccessible(true);

    // Create target file with structure
    $testContent = "# Database Configuration\nDB_HOST=127.0.0.1\nDB_PORT=3306\n\n# API Settings\nAPI_KEY=old_key\n";
    File::put('.env.interactive.test', $testContent);

    // Sync a new value
    $syncMethod->invoke($command, '.env.interactive.test', 'API_KEY', 'new_secret_key', false);

    $result = File::get('.env.interactive.test');
    
    // Should preserve structure but update the value
    expect($result)->toContain('# Database Configuration');
    expect($result)->toContain('DB_HOST=127.0.0.1');
    expect($result)->toContain('DB_PORT=3306');
    expect($result)->toContain('# API Settings');
    expect($result)->toContain('API_KEY=new_secret_key');
    expect($result)->not->toContain('API_KEY=old_key');
});

it('can add #ENVIGNORE comment to specific lines', function () {
    $command = new EnvsyncCommand();
    $reflection = new \ReflectionClass($command);
    $ignoreMethod = $reflection->getMethod('addIgnoreComment');
    $ignoreMethod->setAccessible(true);

    // Create target file
    $testContent = "APP_NAME=TestApp\nDB_HOST=localhost\nAPI_KEY=secret\n";
    File::put('.env.interactive.test', $testContent);

    // Add ignore comment to DB_HOST
    $ignoreMethod->invoke($command, '.env.interactive.test', 'DB_HOST');

    $result = File::get('.env.interactive.test');
    
    expect($result)->toContain('APP_NAME=TestApp');
    expect($result)->toContain('DB_HOST=localhost #ENVIGNORE');
    expect($result)->toContain('API_KEY=secret');
});

it('handles quoted values correctly when syncing', function () {
    $command = new EnvsyncCommand();
    $reflection = new \ReflectionClass($command);
    $syncMethod = $reflection->getMethod('syncValueToTarget');
    $syncMethod->setAccessible(true);

    // Create target file
    File::put('.env.interactive.test', 'APP_NAME="Old App Name"' . "\n");

    // Sync a new value with spaces
    $syncMethod->invoke($command, '.env.interactive.test', 'APP_NAME', 'New App Name', false);

    $result = File::get('.env.interactive.test');
    
    // Should properly quote the new value
    expect($result)->toContain('APP_NAME="New App Name"');
});

it('handles version controlled files correctly when syncing', function () {
    $command = new EnvsyncCommand();
    $reflection = new \ReflectionClass($command);
    $syncMethod = $reflection->getMethod('syncValueToTarget');
    $syncMethod->setAccessible(true);

    // Create target file
    File::put('.env.interactive.test', "API_KEY=old_value\n");

    // Sync with version controlled flag (should clear the value)
    $syncMethod->invoke($command, '.env.interactive.test', 'API_KEY', 'secret_value', true);

    $result = File::get('.env.interactive.test');
    
    // Should clear the value for version controlled files
    expect($result)->toContain('API_KEY=');
    expect($result)->not->toContain('secret_value');
});

it('does not list ignored entries as missing', function () {
    // Create source file
    File::put('.env', "APP_NAME=MyApp\nDB_HOST=localhost\nAPI_KEY=secret\n");
    
    // Create target file with ignored entry
    File::put('.env.interactive.test', "APP_NAME=ExampleApp\nDB_HOST=prod-server #ENVIGNORE\nAPI_KEY=example_key\n");

    $this->artisan('env:sync --path=.env.interactive.test --auto-sync')
        ->expectsOutput('Syncing \'.env\' with \'.env.interactive.test\'')
        ->expectsOutput('Ignored entries (marked with #ENVIGNORE in target file):')
        ->expectsOutput('  DB_HOST - permanently ignored')
        ->expectsOutput('Differing values detected:')
        ->expectsOutput('✓ Synced \'APP_NAME\' from .env to .env.interactive.test')
        ->expectsOutput('✓ Synced \'API_KEY\' from .env to .env.interactive.test')
        ->assertExitCode(0);

    // Verify DB_HOST was not changed (still ignored)
    $targetContent = File::get('.env.interactive.test');
    expect($targetContent)->toContain('DB_HOST=prod-server #ENVIGNORE');
    expect($targetContent)->toContain('APP_NAME=MyApp');
    expect($targetContent)->toContain('API_KEY=secret');
});

it('preserves quotes when syncing values directly', function () {
    $command = new EnvsyncCommand();
    $reflection = new \ReflectionClass($command);
    $syncMethod = $reflection->getMethod('syncValueToTarget');
    $syncMethod->setAccessible(true);

    // Create source file with quoted value
    File::put('.env', 'VITE_APP_NAME="${APP_NAME}"' . "\n");
    
    // Create target file with different quoted value
    File::put('.env.interactive.test', 'VITE_APP_NAME="${OLD_NAME}"' . "\n");

    // Sync the value
    $syncMethod->invoke($command, '.env.interactive.test', 'VITE_APP_NAME', '${APP_NAME}', false);

    // Verify quotes are preserved
    $targetContent = File::get('.env.interactive.test');
    expect($targetContent)->toContain('VITE_APP_NAME="${APP_NAME}"');
    expect($targetContent)->not->toContain('VITE_APP_NAME=${APP_NAME}'); // Should not lose quotes
});
