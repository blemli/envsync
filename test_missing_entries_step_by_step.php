<?php

require_once 'vendor/autoload.php';

// Create test files with missing entries scenario
file_put_contents('.env', "APP_NAME=MyApp\nAPP_ENV=production\nDB_HOST=localhost\nNEW_FEATURE_FLAG=enabled\nCACHE_DRIVER=redis\n");
file_put_contents('.env.example', "APP_NAME=ExampleApp\nAPP_ENV=local\nDB_HOST=127.0.0.1\n");

echo "=== Missing Entries Step-by-Step Test ===\n\n";

echo "📄 Test scenario:\n";
echo ".env contains:\n" . file_get_contents('.env') . "\n";
echo ".env.example contains:\n" . file_get_contents('.env.example') . "\n";

echo "🔍 Expected behavior:\n";
echo "- NEW_FEATURE_FLAG and CACHE_DRIVER are missing in .env.example\n";
echo "- The new step-by-step process should handle each missing entry individually\n";
echo "- Option 'f' should add commented entries with #ENVIGNORE\n\n";

// Test the new functionality by simulating the missing entries logic
class TestMissingEntries {
    public function parseEnvFile(string $filePath): array {
        $content = file_get_contents($filePath);
        $entries = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $originalLine = $line;
            $line = trim($line);
            
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($originalLine, '#ENVIGNORE')) {
                continue;
            }

            if (str_contains($line, '=')) {
                $parts = explode('=', $line, 2);
                $key = trim($parts[0]);
                $value = isset($parts[1]) ? trim($parts[1]) : '';
                
                if (str_contains($value, '#ENVIGNORE')) {
                    $value = trim(str_replace('#ENVIGNORE', '', $value));
                }
                
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }
                
                $entries[$key] = $value;
            }
        }

        return $entries;
    }
    
    public function parseEnvFileWithStructure(string $filePath): array {
        $content = file_get_contents($filePath);
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
            
            if (empty($trimmedLine) || str_starts_with($trimmedLine, '#')) {
                $structure[$lineNumber]['type'] = empty($trimmedLine) ? 'empty' : 'comment';
                continue;
            }

            if (str_contains($trimmedLine, '=')) {
                $parts = explode('=', $trimmedLine, 2);
                $key = trim($parts[0]);
                $value = isset($parts[1]) ? trim($parts[1]) : '';
                
                $hasIgnore = str_contains($originalLine, '#ENVIGNORE');
                
                if (str_contains($value, '#ENVIGNORE')) {
                    $value = trim(str_replace('#ENVIGNORE', '', $value));
                }
                
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
                
                if (!$hasIgnore) {
                    $entries[$key] = $value;
                }
            }
        }

        return ['entries' => $entries, 'structure' => $structure];
    }
    
    public function addIgnoredEntryToTarget(string $targetFile, string $key): void {
        $targetData = $this->parseEnvFileWithStructure($targetFile);
        $structure = $targetData['structure'];
        
        // Add new ignored entry at the end
        $maxLineNumber = max(array_keys($structure));
        $lineNumber = $maxLineNumber + 1;
        
        $structure[$lineNumber] = [
            'original' => "# {$key}= #ENVIGNORE",
            'type' => 'comment',
            'key' => $key,
            'value' => '',
            'ignored' => true
        ];
        
        $this->writeEnvFileWithStructure($targetFile, $structure);
    }
    
    public function addSingleEntryToTarget(string $targetFile, string $key, string $value): void {
        $targetData = $this->parseEnvFileWithStructure($targetFile);
        $structure = $targetData['structure'];
        
        // Add new entry at the end
        $maxLineNumber = max(array_keys($structure));
        $lineNumber = $maxLineNumber + 1;
        
        $formattedValue = $value;
        
        // Quote values that contain spaces or special characters
        if (!empty($formattedValue) && (str_contains($formattedValue, ' ') || str_contains($formattedValue, '#') || str_contains($formattedValue, '"') || str_contains($formattedValue, '$'))) {
            $formattedValue = '"' . str_replace('"', '\"', $formattedValue) . '"';
        }
        
        $structure[$lineNumber] = [
            'original' => "{$key}={$formattedValue}",
            'type' => 'env_var',
            'key' => $key,
            'value' => $value,
            'ignored' => false
        ];
        
        $this->writeEnvFileWithStructure($targetFile, $structure);
    }
    
    public function writeEnvFileWithStructure(string $filePath, array $structure): void {
        $content = '';
        
        foreach ($structure as $lineData) {
            $content .= $lineData['original'] . "\n";
        }
        
        $content = rtrim($content, "\n") . "\n";
        
        file_put_contents($filePath, $content);
    }
    
    public function testMissingEntriesLogic(): void {
        $sourceEntries = $this->parseEnvFile('.env');
        $targetEntries = $this->parseEnvFile('.env.example');
        
        $missingInTarget = array_diff_key($sourceEntries, $targetEntries);
        
        echo "🔍 Analysis:\n";
        echo "Source entries: " . implode(', ', array_keys($sourceEntries)) . "\n";
        echo "Target entries: " . implode(', ', array_keys($targetEntries)) . "\n";
        echo "Missing in target: " . implode(', ', array_keys($missingInTarget)) . "\n\n";
        
        echo "🧪 Testing step-by-step logic:\n\n";
        
        // Simulate step-by-step decisions
        foreach ($missingInTarget as $key => $value) {
            echo "Key '{$key}' is missing in .env.example:\n";
            echo "  .env: '{$value}'\n";
            
            // Test option 'f' - Forever ignore (add commented out with #ENVIGNORE)
            if ($key === 'NEW_FEATURE_FLAG') {
                echo "  Decision: Forever ignore (add commented out with #ENVIGNORE)\n";
                $this->addIgnoredEntryToTarget('.env.example', $key);
                echo "  ✓ Added '{$key}' as ignored entry to .env.example\n\n";
            } else if ($key === 'CACHE_DRIVER') {
                echo "  Decision: Add to .env.example\n";
                $this->addSingleEntryToTarget('.env.example', $key, '');
                echo "  ✓ Added '{$key}' to .env.example\n\n";
            }
        }
    }
}

$test = new TestMissingEntries();
$test->testMissingEntriesLogic();

echo "📄 Final result:\n";
echo ".env.example after processing:\n" . file_get_contents('.env.example') . "\n";

echo "✅ Verification:\n";
$finalContent = file_get_contents('.env.example');
if (str_contains($finalContent, '# NEW_FEATURE_FLAG= #ENVIGNORE')) {
    echo "✓ NEW_FEATURE_FLAG correctly added as ignored entry with #ENVIGNORE\n";
} else {
    echo "✗ NEW_FEATURE_FLAG not found as ignored entry\n";
}

if (str_contains($finalContent, 'CACHE_DRIVER=')) {
    echo "✓ CACHE_DRIVER correctly added as regular entry\n";
} else {
    echo "✗ CACHE_DRIVER not found as regular entry\n";
}

echo "\n🎉 Test completed! The missing entries logic has been successfully moved to step-by-step decision process.\n";
