<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Clean up any existing test files
    $files = ['.env.remove.test', '.env.example.remove.test'];
    foreach ($files as $file) {
        if (File::exists($file)) {
            File::delete($file);
        }
    }
});

afterEach(function () {
    // Clean up test files after each test
    $files = ['.env.remove.test', '.env.example.remove.test'];
    foreach ($files as $file) {
        if (File::exists($file)) {
            File::delete($file);
        }
    }
});

it('shows warning for remove operation on unversioned files', function () {
    // Create source file with extra keys
    $sourceContent = "APP_NAME=TestApp\nAPP_ENV=local\nEXTRA_KEY_1=value1\nEXTRA_KEY_2=value2";
    File::put('.env.remove.test', $sourceContent);
    
    // Create target file with fewer keys
    $targetContent = "APP_NAME=\nAPP_ENV=";
    File::put('.env.example.remove.test', $targetContent);
    
    // Mock the source file as the .env file for the command
    File::copy('.env.remove.test', '.env');
    
    $this->artisan('env:sync --path=.env.example.remove.test --remove')
        ->expectsOutput('⚠️  REMOVE MODE: Keys found in \'.env\' but missing in \'.env.example.remove.test\':')
        ->expectsOutput('  EXTRA_KEY_1=value1')
        ->expectsOutput('  EXTRA_KEY_2=value2')
        ->expectsOutput('🚨 WARNING: \'.env\' is NOT version controlled!')
        ->expectsOutput('   Removing keys from unversioned files can result in permanent data loss.')
        ->expectsConfirmation('Are you sure you want to remove these 2 keys from \'.env\'?', 'no')
        ->expectsOutput('❌ Remove operation cancelled')
        ->assertExitCode(0);
        
    // Restore original .env
    File::delete('.env');
    File::put('.env', 'APP_NAME=TestApp');
});

it('removes keys when confirmed interactively', function () {
    // Create source file with extra keys
    $sourceContent = "APP_NAME=TestApp\nAPP_ENV=local\nEXTRA_KEY_1=value1\nEXTRA_KEY_2=value2";
    File::put('.env.remove.test', $sourceContent);
    
    // Create target file with fewer keys
    $targetContent = "APP_NAME=\nAPP_ENV=";
    File::put('.env.example.remove.test', $targetContent);
    
    // Mock the source file as the .env file for the command
    File::copy('.env.remove.test', '.env');
    
    $this->artisan('env:sync --path=.env.example.remove.test --remove')
        ->expectsOutput('⚠️  REMOVE MODE: Keys found in \'.env\' but missing in \'.env.example.remove.test\':')
        ->expectsConfirmation('Are you sure you want to remove these 2 keys from \'.env\'?', 'yes')
        ->expectsConfirmation('⚠️  FINAL WARNING: \'.env\' is not version controlled. This action cannot be undone. Continue?', 'yes')
        ->expectsOutput('✓ Removed 2 keys from \'.env\'')
        ->assertExitCode(0);
    
    // Verify keys were removed
    $finalContent = File::get('.env');
    expect($finalContent)->not->toContain('EXTRA_KEY_1');
    expect($finalContent)->not->toContain('EXTRA_KEY_2');
    expect($finalContent)->toContain('APP_NAME=TestApp');
    expect($finalContent)->toContain('APP_ENV=local');
    
    // Restore original .env
    File::delete('.env');
    File::put('.env', 'APP_NAME=TestApp');
});

it('prevents force remove on unversioned files', function () {
    // Create source file with extra keys
    $sourceContent = "APP_NAME=TestApp\nAPP_ENV=local\nEXTRA_KEY_1=value1";
    File::put('.env.remove.test', $sourceContent);
    
    // Create target file with fewer keys
    $targetContent = "APP_NAME=\nAPP_ENV=";
    File::put('.env.example.remove.test', $targetContent);
    
    // Mock the source file as the .env file for the command
    File::copy('.env.remove.test', '.env');
    
    $this->artisan('env:sync --path=.env.example.remove.test --remove --force')
        ->expectsOutput('⚠️  REMOVE MODE: Keys found in \'.env\' but missing in \'.env.example.remove.test\':')
        ->expectsOutput('🚨 WARNING: \'.env\' is NOT version controlled!')
        ->expectsOutput('❌ Cannot use --force with --remove on unversioned files for safety reasons.')
        ->expectsOutput('   Please version control \'.env\' first or run without --force for confirmation prompts.')
        ->assertExitCode(0);
    
    // Verify no keys were removed
    $finalContent = File::get('.env');
    expect($finalContent)->toContain('EXTRA_KEY_1=value1');
    
    // Restore original .env
    File::delete('.env');
    File::put('.env', 'APP_NAME=TestApp');
});

it('does nothing when no keys need to be removed', function () {
    // Create source and target files with same keys
    $sourceContent = "APP_NAME=TestApp\nAPP_ENV=local";
    File::put('.env.remove.test', $sourceContent);
    
    $targetContent = "APP_NAME=\nAPP_ENV=";
    File::put('.env.example.remove.test', $targetContent);
    
    // Mock the source file as the .env file for the command
    File::copy('.env.remove.test', '.env');
    
    $this->artisan('env:sync --path=.env.example.remove.test --remove')
        ->expectsOutput('Files are already in sync. No changes needed.')
        ->assertExitCode(0);
    
    // Restore original .env
    File::delete('.env');
    File::put('.env', 'APP_NAME=TestApp');
});
