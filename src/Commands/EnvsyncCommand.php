<?php

namespace Blemli\Envsync\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class EnvsyncCommand extends Command
{
    public $signature = 'env:sync {--path=.env.example : Path to the target file to sync with} {--force : Skip confirmation prompts and apply all changes automatically} {--auto-sync : Automatically sync all differing values from source to target}';

    public $description = 'Sync .env files by comparing keys and prompting for missing entries';

    public function handle(): int
    {
        $sourceFile = '.env';
        $targetFile = $this->option('path');

        // Check if source file exists
        if (!File::exists($sourceFile)) {
            $this->error("Source file '{$sourceFile}' does not exist.");
            return self::FAILURE;
        }

        // Check if target file exists
        if (!File::exists($targetFile)) {
            $this->error("Target file '{$targetFile}' does not exist.");
            return self::FAILURE;
        }

        $this->info("Syncing '{$sourceFile}' with '{$targetFile}'");

        // Parse both files
        $sourceEntries = $this->parseEnvFile($sourceFile);
        $targetEntries = $this->parseEnvFile($targetFile);

        // Determine if target file is version controlled
        $isVersionControlled = $this->isVersionControlled($targetFile);
        
        if ($isVersionControlled) {
            $this->info("Target file '{$targetFile}' is version controlled - values will be removed, only keys will be kept.");
        } else {
            $this->info("Target file '{$targetFile}' is not version controlled - values will be copied.");
        }

        // Parse target file with structure to get ignored entries
        $targetData = $this->parseEnvFileWithStructure($targetFile);
        $ignoredKeys = [];
        foreach ($targetData['structure'] as $lineData) {
            if ($lineData['type'] === 'env_var' && isset($lineData['ignored']) && $lineData['ignored']) {
                $ignoredKeys[] = $lineData['key'];
            }
        }

        // Find differences, accounting for ignored entries
        $missingInTarget = array_diff_key($sourceEntries, $targetEntries);
        $missingInSource = array_diff_key($targetEntries, $sourceEntries);
        
        // Remove ignored keys from missing lists and show info about them
        $ignoredInTarget = array_intersect($ignoredKeys, array_keys($sourceEntries));
        if (!empty($ignoredInTarget)) {
            $this->info("\nIgnored entries (marked with #ENVIGNORE in target file):");
            foreach ($ignoredInTarget as $key) {
                $this->line("  <comment>{$key}</comment> - permanently ignored");
                // Remove from missing list since it's intentionally ignored
                unset($missingInTarget[$key]);
            }
        }

        $modified = false;

        // Handle differing values interactively
        $targetModified = $this->handleDifferingValues($sourceEntries, $targetEntries, $sourceFile, $targetFile, $isVersionControlled);
        if ($targetModified) {
            $modified = true;
        }

        // Handle entries missing in target
        if (!empty($missingInTarget)) {
            $this->info("\nEntries found in '{$sourceFile}' but missing in '{$targetFile}':");
            foreach ($missingInTarget as $key => $value) {
                $this->line("  {$key}={$value}");
            }

            $shouldAdd = $this->option('force') || $this->confirm("Add these entries to '{$targetFile}'?");
            if ($shouldAdd) {
                foreach ($missingInTarget as $key => $value) {
                    if ($isVersionControlled) {
                        $targetEntries[$key] = '';
                    } else {
                        $targetEntries[$key] = $value;
                    }
                }
                $modified = true;
                if ($this->option('force')) {
                    $this->info("Automatically adding entries (--force enabled)");
                }
            }
        }

        // Handle entries missing in source
        if (!empty($missingInSource)) {
            $this->info("\nEntries found in '{$targetFile}' but missing in '{$sourceFile}':");
            foreach ($missingInSource as $key => $value) {
                $this->line("  {$key}={$value}");
            }

            $shouldRemove = $this->option('force') || $this->confirm("Remove these entries from '{$targetFile}'?");
            if ($shouldRemove) {
                foreach ($missingInSource as $key => $value) {
                    unset($targetEntries[$key]);
                }
                $modified = true;
                if ($this->option('force')) {
                    $this->info("Automatically removing entries (--force enabled)");
                }
            }
        }

        // Write back to target file if modified (only for missing/extra entries, not differing values)
        if ($modified && (!empty($missingInTarget) || !empty($missingInSource))) {
            $this->writeEnvFile($targetFile, $targetEntries);
        }

        // Final status message
        if ($modified) {
            $this->info("\nSuccessfully synced '{$targetFile}' with '{$sourceFile}'");
        } else {
            // Check if there were any differences at all
            $sourceEntries = $this->parseEnvFile($sourceFile);
            $targetEntries = $this->parseEnvFile($targetFile);
            $commonKeys = array_intersect_key($sourceEntries, $targetEntries);
            $hasDifferingValues = false;
            
            foreach ($commonKeys as $key => $sourceValue) {
                $targetValue = $targetEntries[$key];
                if (!empty($sourceValue) && !empty($targetValue) && $sourceValue !== $targetValue) {
                    $hasDifferingValues = true;
                    break;
                }
            }
            
            if (empty($missingInTarget) && empty($missingInSource) && !$hasDifferingValues) {
                $this->info("\nFiles are already in sync. No changes needed.");
            } else {
                $this->info("\nNo changes made to '{$targetFile}'");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Parse an .env file and return key-value pairs, preserving structure
     */
    private function parseEnvFile(string $filePath): array
    {
        $content = File::get($filePath);
        $entries = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $originalLine = $line;
            $line = trim($line);
            
            // Skip empty lines and comments
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Skip lines with #ENVIGNORE comment
            if (str_contains($originalLine, '#ENVIGNORE')) {
                continue;
            }

            // Parse key=value pairs
            if (str_contains($line, '=')) {
                $parts = explode('=', $line, 2);
                $key = trim($parts[0]);
                $value = isset($parts[1]) ? trim($parts[1]) : '';
                
                // Remove #ENVIGNORE comment if present in value
                if (str_contains($value, '#ENVIGNORE')) {
                    $value = trim(str_replace('#ENVIGNORE', '', $value));
                }
                
                // Remove quotes if present
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }
                
                $entries[$key] = $value;
            }
        }

        return $entries;
    }

    /**
     * Parse an .env file and return structured data with original lines
     */
    private function parseEnvFileWithStructure(string $filePath): array
    {
        $content = File::get($filePath);
        $lines = explode("\n", $content);
        $entries = [];
        $structure = [];

        foreach ($lines as $lineNumber => $line) {
            $originalLine = $line;
            $trimmedLine = trim($line);
            
            $structure[$lineNumber] = [
                'original' => $originalLine,
                'type' => 'other'
            ];
            
            // Handle empty lines and comments
            if (empty($trimmedLine) || str_starts_with($trimmedLine, '#')) {
                $structure[$lineNumber]['type'] = empty($trimmedLine) ? 'empty' : 'comment';
                continue;
            }

            // Parse key=value pairs
            if (str_contains($trimmedLine, '=')) {
                $parts = explode('=', $trimmedLine, 2);
                $key = trim($parts[0]);
                $value = isset($parts[1]) ? trim($parts[1]) : '';
                
                // Check if line has #ENVIGNORE
                $hasIgnore = str_contains($originalLine, '#ENVIGNORE');
                
                // Remove #ENVIGNORE comment from value for processing
                if (str_contains($value, '#ENVIGNORE')) {
                    $value = trim(str_replace('#ENVIGNORE', '', $value));
                }
                
                // Remove quotes if present
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }
                
                $structure[$lineNumber] = [
                    'original' => $originalLine,
                    'type' => 'env_var',
                    'key' => $key,
                    'value' => $value,
                    'ignored' => $hasIgnore
                ];
                
                // Only include in entries if not ignored
                if (!$hasIgnore) {
                    $entries[$key] = $value;
                }
            }
        }

        return ['entries' => $entries, 'structure' => $structure];
    }

    /**
     * Write entries back to an .env file
     */
    private function writeEnvFile(string $filePath, array $entries): void
    {
        $content = '';
        
        foreach ($entries as $key => $value) {
            // Quote values that contain spaces or special characters
            if (str_contains($value, ' ') || str_contains($value, '#') || str_contains($value, '"')) {
                $value = '"' . str_replace('"', '\"', $value) . '"';
            }
            
            $content .= "{$key}={$value}\n";
        }

        File::put($filePath, $content);
    }

    /**
     * Handle differing values between source and target files interactively
     */
    private function handleDifferingValues(array $sourceEntries, array $targetEntries, string $sourceFile, string $targetFile, bool $isTargetVersionControlled): bool
    {
        $modified = false;
        $differingKeys = [];
        
        // Find keys with different values
        $commonKeys = array_intersect_key($sourceEntries, $targetEntries);
        foreach ($commonKeys as $key => $sourceValue) {
            $targetValue = $targetEntries[$key];
            
            // Only consider if both values are non-empty and different
            if (!empty($sourceValue) && !empty($targetValue) && $sourceValue !== $targetValue) {
                $differingKeys[$key] = [
                    'source' => $sourceValue,
                    'target' => $targetValue
                ];
            }
        }
        
        if (empty($differingKeys)) {
            return false;
        }
        
        $this->warn("\nDiffering values detected:");
        
        foreach ($differingKeys as $key => $values) {
            $this->line("");
            $this->line("Key '<comment>{$key}</comment>' has different values:");
            $this->line("  {$sourceFile}: '<info>{$values['source']}</info>'");
            $this->line("  {$targetFile}: '<comment>{$values['target']}</comment>'");
            
            if ($this->option('force') || $this->option('auto-sync')) {
                // In force or auto-sync mode, automatically sync from source to target
                $choice = 's';
                if ($this->option('force')) {
                    $this->info("Automatically syncing (--force enabled)");
                } else {
                    $this->info("Automatically syncing (--auto-sync enabled)");
                }
            } else {
                $choice = $this->choice(
                    'What would you like to do with this key?',
                    [
                        's' => 'Sync from ' . $sourceFile . ' to ' . $targetFile,
                        'i' => 'Ignore once (skip this time only)',
                        'f' => 'Forever ignore (add #ENVIGNORE to this line)',
                        'q' => 'Quit without making changes'
                    ],
                    's'
                );
            }
            
            switch ($choice) {
                case 's':
                    // Sync value from source to target
                    $this->syncValueToTarget($targetFile, $key, $values['source'], $isTargetVersionControlled);
                    $modified = true;
                    $this->info("✓ Synced '{$key}' from {$sourceFile} to {$targetFile}");
                    break;
                    
                case 'i':
                    // Ignore once - do nothing
                    $this->line("⏭ Skipped '{$key}' for this run");
                    break;
                    
                case 'f':
                    // Add #ENVIGNORE to the target file line
                    $this->addIgnoreComment($targetFile, $key);
                    $modified = true;
                    $this->info("🔇 Added #ENVIGNORE to '{$key}' in {$targetFile}");
                    break;
                    
                case 'q':
                    $this->info("Exiting without making changes");
                    return $modified;
            }
        }
        
        return $modified;
    }
    
    /**
     * Sync a specific value from source to target file
     */
    private function syncValueToTarget(string $targetFile, string $key, string $newValue, bool $isVersionControlled): void
    {
        $targetData = $this->parseEnvFileWithStructure($targetFile);
        $structure = $targetData['structure'];
        
        // Find the line with this key and update it
        foreach ($structure as $lineNumber => $lineData) {
            if ($lineData['type'] === 'env_var' && $lineData['key'] === $key) {
                // Format the new value
                $formattedValue = $newValue;
                if ($isVersionControlled) {
                    $formattedValue = ''; // Clear value for version controlled files
                }
                
                // Quote if necessary
                if (str_contains($formattedValue, ' ') || str_contains($formattedValue, '#') || str_contains($formattedValue, '"')) {
                    $formattedValue = '"' . str_replace('"', '\"', $formattedValue) . '"';
                }
                
                $structure[$lineNumber]['original'] = "{$key}={$formattedValue}";
                $structure[$lineNumber]['value'] = $isVersionControlled ? '' : $newValue;
                break;
            }
        }
        
        $this->writeEnvFileWithStructure($targetFile, $structure);
    }
    
    /**
     * Add #ENVIGNORE comment to a specific key in the target file
     */
    private function addIgnoreComment(string $targetFile, string $key): void
    {
        $targetData = $this->parseEnvFileWithStructure($targetFile);
        $structure = $targetData['structure'];
        
        // Find the line with this key and add #ENVIGNORE
        foreach ($structure as $lineNumber => $lineData) {
            if ($lineData['type'] === 'env_var' && $lineData['key'] === $key) {
                $originalLine = $lineData['original'];
                
                // Add #ENVIGNORE if not already present
                if (!str_contains($originalLine, '#ENVIGNORE')) {
                    $structure[$lineNumber]['original'] = rtrim($originalLine) . ' #ENVIGNORE';
                    $structure[$lineNumber]['ignored'] = true;
                }
                break;
            }
        }
        
        $this->writeEnvFileWithStructure($targetFile, $structure);
    }
    
    /**
     * Write env file preserving original structure
     */
    private function writeEnvFileWithStructure(string $filePath, array $structure): void
    {
        $content = '';
        
        foreach ($structure as $lineData) {
            $content .= $lineData['original'] . "\n";
        }
        
        // Remove trailing newline if the original didn't have one
        $content = rtrim($content, "\n") . "\n";
        
        File::put($filePath, $content);
    }

    /**
     * Check if a file is version controlled (in git)
     */
    private function isVersionControlled(string $filePath): bool
    {
        // Check if git is available and if the file is tracked
        if (!File::exists('.git')) {
            return false;
        }

        $output = shell_exec("git ls-files --error-unmatch " . escapeshellarg($filePath) . " 2>/dev/null");
        return !empty($output);
    }
}
