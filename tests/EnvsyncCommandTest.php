<?php

use Illuminate\Support\Facades\File;
use Blemli\Envsync\Commands\EnvsyncCommand;

beforeEach(function () {
    // Clean up any existing test files
    $files = ['.env.test', '.env.example.test', '.env.production.test', '.env.parse.test', '.env.write.test'];
    foreach ($files as $file) {
        if (File::exists($file)) {
            File::delete($file);
        }
    }
});

afterEach(function () {
    // Clean up test files after each test
    $files = ['.env.test', '.env.example.test', '.env.production.test', '.env.parse.test', '.env.write.test'];
    foreach ($files as $file) {
        if (File::exists($file)) {
            File::delete($file);
        }
    }
});

it('handles missing source file', function () {
    // Make sure .env doesn't exist
    if (File::exists('.env')) {
        File::delete('.env');
    }
    
    $this->artisan('env:sync', ['--path' => '.env.example'])
        ->expectsOutput("Source file '.env' does not exist.")
        ->assertExitCode(1);
});

it('handles missing target file', function () {
    File::put('.env', "APP_NAME=TestApp\n");
    
    $this->artisan('env:sync', ['--path' => '.env.nonexistent'])
        ->expectsOutput("Target file '.env.nonexistent' does not exist.")
        ->assertExitCode(1);
        
    File::delete('.env');
});

it('can parse env files correctly', function () {
    $command = new EnvsyncCommand();
    $reflection = new \ReflectionClass($command);
    $method = $reflection->getMethod('parseEnvFile');
    $method->setAccessible(true);

    // Create test file with various formats
    $testContent = "APP_NAME=TestApp\nAPP_KEY=\"quoted value\"\nAPP_DEBUG=true\n# Comment line\n\nEMPTY_VALUE=\n";
    File::put('.env.parse.test', $testContent);

    $result = $method->invoke($command, '.env.parse.test');

    expect($result)->toBe([
        'APP_NAME' => 'TestApp',
        'APP_KEY' => 'quoted value',
        'APP_DEBUG' => 'true',
        'EMPTY_VALUE' => ''
    ]);
});

it('can write env files correctly', function () {
    $command = new EnvsyncCommand();
    $reflection = new \ReflectionClass($command);
    $method = $reflection->getMethod('writeEnvFile');
    $method->setAccessible(true);

    $entries = [
        'APP_NAME' => 'TestApp',
        'APP_KEY' => 'value with spaces',
        'APP_DEBUG' => 'true'
    ];

    $method->invoke($command, '.env.write.test', $entries);

    $content = File::get('.env.write.test');
    expect($content)->toContain('APP_NAME=TestApp');
    expect($content)->toContain('APP_KEY="value with spaces"');
    expect($content)->toContain('APP_DEBUG=true');
});
