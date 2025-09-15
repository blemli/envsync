<?php

require_once 'vendor/autoload.php';

// Create test files
file_put_contents('.env', 'VITE_APP_NAME="${APP_NAME}"' . "\n");
file_put_contents('.env.test', 'VITE_APP_NAME="${OLD_NAME}"' . "\n");

echo "=== Direct Method Test ===\n\n";

echo "📄 Before sync:\n";
echo ".env: " . file_get_contents('.env');
echo ".env.test: " . file_get_contents('.env.test');

// Test the sync method directly without Laravel facades
// We'll create a simple version that doesn't use File facade

class SimpleEnvSync {
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
    
    public function syncValueToTarget(string $targetFile, string $key, string $newValue): void {
        $targetData = $this->parseEnvFileWithStructure($targetFile);
        $structure = $targetData['structure'];
        
        // Get the original value from source to preserve quoting
        $sourceData = $this->parseEnvFileWithStructure('.env');
        $originalQuoted = false;
        foreach ($sourceData['structure'] as $sourceLineData) {
            if ($sourceLineData['type'] === 'env_var' && $sourceLineData['key'] === $key) {
                $originalLine = $sourceLineData['original'];
                if (preg_match('/=\s*"/', $originalLine) || preg_match("/=\s*'/", $originalLine)) {
                    $originalQuoted = true;
                }
                break;
            }
        }
        
        echo "🔍 Quote detection for key '{$key}':\n";
        echo "  Original quoted: " . ($originalQuoted ? 'YES' : 'NO') . "\n";
        
        // Find the line with this key and update it
        foreach ($structure as $lineNumber => $lineData) {
            if ($lineData['type'] === 'env_var' && $lineData['key'] === $key) {
                $formattedValue = $newValue;
                
                if ($originalQuoted || str_contains($formattedValue, ' ') || str_contains($formattedValue, '#') || str_contains($formattedValue, '"') || str_contains($formattedValue, '$')) {
                    $formattedValue = '"' . str_replace('"', '\"', $formattedValue) . '"';
                }
                
                echo "  Formatted value: '{$formattedValue}'\n";
                
                $structure[$lineNumber]['original'] = "{$key}={$formattedValue}";
                break;
            }
        }
        
        $content = '';
        foreach ($structure as $lineData) {
            $content .= $lineData['original'] . "\n";
        }
        $content = rtrim($content, "\n") . "\n";
        
        file_put_contents($targetFile, $content);
    }
}

$sync = new SimpleEnvSync();
$sync->syncValueToTarget('.env.test', 'VITE_APP_NAME', '${APP_NAME}');

echo "\n📄 After sync:\n";
echo ".env.test: " . file_get_contents('.env.test');

echo "\n✅ Expected: VITE_APP_NAME=\"\${APP_NAME}\"\n";
echo "✅ Result shows quotes are " . (str_contains(file_get_contents('.env.test'), 'VITE_APP_NAME="${APP_NAME}"') ? 'PRESERVED' : 'MISSING') . "\n";
