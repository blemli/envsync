<?php

use Illuminate\Support\Facades\File;

it('can sync env files with version controlled target (removes values)', function () {
    // Create source file with more entries
    File::put('.env', "APP_NAME=TestApp\nAPP_ENV=local\nCUSTOM_KEY=secret\nNEW_FEATURE=enabled\n");
    
    // Create target file with fewer entries
    File::put('.env.example', "APP_NAME=TestApp\nAPP_ENV=local\n");

    $this->artisan('env:sync', ['--path' => '.env.example'])
        ->expectsQuestion("Add these entries to '.env.example'?", 'yes')
        ->assertExitCode(0);

    // Verify the target file was updated with empty values (version controlled)
    $targetContent = File::get('.env.example');
    expect($targetContent)->toContain('CUSTOM_KEY=');
    expect($targetContent)->toContain('NEW_FEATURE=');
    expect($targetContent)->not->toContain('CUSTOM_KEY=secret');
    expect($targetContent)->not->toContain('NEW_FEATURE=enabled');
    
    // Clean up
    File::delete('.env');
    File::delete('.env.example');
});

it('can sync env files with non-version controlled target (copies values)', function () {
    // Create source file
    File::put('.env', "APP_NAME=TestApp\nAPP_KEY=secret-key\nDB_PASSWORD=secret123\n");
    
    // Create target file (not version controlled)
    File::put('.env.local', "APP_NAME=TestApp\n");

    $this->artisan('env:sync', ['--path' => '.env.local'])
        ->expectsQuestion("Add these entries to '.env.local'?", 'yes')
        ->assertExitCode(0);

    // Verify the target file was updated with actual values (not version controlled)
    $targetContent = File::get('.env.local');
    expect($targetContent)->toContain('APP_KEY=secret-key');
    expect($targetContent)->toContain('DB_PASSWORD=secret123');
    
    // Clean up
    File::delete('.env');
    File::delete('.env.local');
});

it('can remove entries from target that are missing in source', function () {
    // Create source file with fewer entries
    File::put('.env', "APP_NAME=TestApp\nAPP_ENV=local\n");
    
    // Create target file with more entries
    File::put('.env.production', "APP_NAME=TestApp\nAPP_ENV=production\nOLD_KEY=old_value\nDEPRECATED_SETTING=true\n");

    $this->artisan('env:sync', ['--path' => '.env.production'])
        ->expectsQuestion("Remove these entries from '.env.production'?", 'yes')
        ->assertExitCode(0);

    // Verify the entries were removed from target
    $targetContent = File::get('.env.production');
    expect($targetContent)->not->toContain('OLD_KEY');
    expect($targetContent)->not->toContain('DEPRECATED_SETTING');
    expect($targetContent)->toContain('APP_NAME=TestApp');
    expect($targetContent)->toContain('APP_ENV=production');
    
    // Clean up
    File::delete('.env');
    File::delete('.env.production');
});

it('handles files that are already in sync', function () {
    // Create identical files
    $content = "APP_NAME=TestApp\nAPP_ENV=local\nDB_HOST=localhost\n";
    File::put('.env', $content);
    File::put('.env.test', $content);

    $this->artisan('env:sync', ['--path' => '.env.test'])
        ->assertExitCode(0);
    
    // Clean up
    File::delete('.env');
    File::delete('.env.test');
});

it('handles user declining to make changes', function () {
    // Create source file with more entries
    File::put('.env', "APP_NAME=TestApp\nNEW_KEY=new_value\n");
    
    // Create target file with fewer entries - store original content
    $originalContent = "APP_NAME=TestApp\n";
    File::put('.env.test', $originalContent);

    $this->artisan('env:sync', ['--path' => '.env.test'])
        ->expectsQuestion("Add these entries to '.env.test'?", 'no')
        ->assertExitCode(0);

    // Verify no changes were made - content should be exactly the same
    $targetContent = File::get('.env.test');
    expect($targetContent)->toBe($originalContent);
    expect($targetContent)->not->toContain('NEW_KEY');
    
    // Clean up
    File::delete('.env');
    File::delete('.env.test');
});

it('can force sync without prompts - adding entries', function () {
    // Create source file with more entries
    File::put('.env', "APP_NAME=TestApp\nNEW_KEY=new_value\nANOTHER_KEY=another_value\n");
    
    // Create target file with fewer entries
    File::put('.env.test', "APP_NAME=TestApp\n");

    $this->artisan('env:sync', ['--path' => '.env.test', '--force' => true])
        ->expectsOutput('Automatically adding entries (--force enabled)')
        ->assertExitCode(0);

    // Verify entries were added automatically
    $targetContent = File::get('.env.test');
    expect($targetContent)->toContain('NEW_KEY=new_value');
    expect($targetContent)->toContain('ANOTHER_KEY=another_value');
    
    // Clean up
    File::delete('.env');
    File::delete('.env.test');
});

it('can force sync without prompts - removing entries', function () {
    // Create source file with fewer entries
    File::put('.env', "APP_NAME=TestApp\n");
    
    // Create target file with more entries
    File::put('.env.test', "APP_NAME=TestApp\nOLD_KEY=old_value\nDEPRECATED_KEY=deprecated\n");

    $this->artisan('env:sync', ['--path' => '.env.test', '--force' => true])
        ->expectsOutput('Automatically removing entries (--force enabled)')
        ->assertExitCode(0);

    // Verify entries were removed automatically
    $targetContent = File::get('.env.test');
    expect($targetContent)->not->toContain('OLD_KEY');
    expect($targetContent)->not->toContain('DEPRECATED_KEY');
    expect($targetContent)->toContain('APP_NAME=TestApp');
    
    // Clean up
    File::delete('.env');
    File::delete('.env.test');
});

it('can force sync with both adding and removing entries', function () {
    // Create source file
    File::put('.env', "APP_NAME=TestApp\nNEW_KEY=new_value\nKEEP_KEY=keep_this\n");
    
    // Create target file
    File::put('.env.test', "APP_NAME=TestApp\nOLD_KEY=old_value\nKEEP_KEY=keep_this\n");

    $this->artisan('env:sync', ['--path' => '.env.test', '--force' => true])
        ->expectsOutput('Automatically adding entries (--force enabled)')
        ->expectsOutput('Automatically removing entries (--force enabled)')
        ->assertExitCode(0);

    // Verify changes were made automatically
    $targetContent = File::get('.env.test');
    expect($targetContent)->toContain('NEW_KEY=new_value'); // Added
    expect($targetContent)->not->toContain('OLD_KEY'); // Removed
    expect($targetContent)->toContain('KEEP_KEY=keep_this'); // Kept
    expect($targetContent)->toContain('APP_NAME=TestApp'); // Kept
    
    // Clean up
    File::delete('.env');
    File::delete('.env.test');
});

it('can force sync with version controlled target file', function () {
    // Create source file
    File::put('.env', "APP_NAME=TestApp\nNEW_SECRET=secret_value\n");
    
    // Create version controlled target file (.env.example is typically version controlled)
    File::put('.env.example', "APP_NAME=TestApp\n");

    $this->artisan('env:sync', ['--path' => '.env.example', '--force' => true])
        ->expectsOutput('Automatically adding entries (--force enabled)')
        ->assertExitCode(0);

    // Verify entry was added with empty value (version controlled behavior)
    $targetContent = File::get('.env.example');
    expect($targetContent)->toContain('NEW_SECRET='); // Empty value for version controlled
    expect($targetContent)->not->toContain('NEW_SECRET=secret_value'); // Should not contain actual value
    
    // Clean up
    File::delete('.env');
    File::delete('.env.example');
});
