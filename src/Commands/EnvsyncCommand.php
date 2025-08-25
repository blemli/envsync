<?php

namespace Blemli\Envsync\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class EnvsyncCommand extends Command
{
    public $signature = 'env:sync {--path=.env.example : Path to the target file to sync with} {--force : Skip confirmation prompts and apply all changes automatically}';

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

        // Find differences
        $missingInTarget = array_diff_key($sourceEntries, $targetEntries);
        $missingInSource = array_diff_key($targetEntries, $sourceEntries);

        // Check for differing values and empty values
        $this->checkForWarnings($sourceEntries, $targetEntries, $sourceFile, $targetFile, $isVersionControlled);

        $modified = false;

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

        // If no differences found
        if (empty($missingInTarget) && empty($missingInSource)) {
            $this->info("\nFiles are already in sync. No changes needed.");
            return self::SUCCESS;
        }

        // Write back to target file if modified
        if ($modified) {
            $this->writeEnvFile($targetFile, $targetEntries);
            $this->info("\nSuccessfully synced '{$targetFile}' with '{$sourceFile}'");
        } else {
            $this->info("\nNo changes made to '{$targetFile}'");
        }

        return self::SUCCESS;
    }

    /**
     * Parse an .env file and return key-value pairs
     */
    private function parseEnvFile(string $filePath): array
    {
        $content = File::get($filePath);
        $entries = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines and comments
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Parse key=value pairs
            if (str_contains($line, '=')) {
                $parts = explode('=', $line, 2);
                $key = trim($parts[0]);
                $value = isset($parts[1]) ? trim($parts[1]) : '';
                
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
     * Check for warnings about differing values and empty values
     */
    private function checkForWarnings(array $sourceEntries, array $targetEntries, string $sourceFile, string $targetFile, bool $isTargetVersionControlled): void
    {
        $warnings = [];
        
        // Check for differing non-empty values
        $commonKeys = array_intersect_key($sourceEntries, $targetEntries);
        foreach ($commonKeys as $key => $sourceValue) {
            $targetValue = $targetEntries[$key];
            
            // Only warn if both values are non-empty and different
            if (!empty($sourceValue) && !empty($targetValue) && $sourceValue !== $targetValue) {
                $warnings[] = "Key '{$key}' has different values: '{$sourceFile}' = '{$sourceValue}', '{$targetFile}' = '{$targetValue}'";
            }
        }
        
        // Check for empty values in version-controlled files
        if ($isTargetVersionControlled) {
            foreach ($targetEntries as $key => $value) {
                if (empty($value)) {
                    $warnings[] = "Key '{$key}' has empty value in version-controlled file '{$targetFile}'";
                }
            }
        }
        
        // Check if source file is also version controlled and has empty values
        $isSourceVersionControlled = $this->isVersionControlled('.env');
        if ($isSourceVersionControlled) {
            foreach ($sourceEntries as $key => $value) {
                if (empty($value)) {
                    $warnings[] = "Key '{$key}' has empty value in version-controlled file '{$sourceFile}'";
                }
            }
        }
        
        // Display warnings if any
        if (!empty($warnings)) {
            $this->warn("\nWarnings detected:");
            foreach ($warnings as $warning) {
                $this->line("  ⚠️  {$warning}");
            }
        }
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
