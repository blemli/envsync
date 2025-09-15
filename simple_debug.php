<?php

// Simple debug without Laravel facades
file_put_contents('.env', 'VITE_APP_NAME="${APP_NAME}"' . "\n");
file_put_contents('.env.example', 'VITE_APP_NAME="${OLD_NAME}"' . "\n");

echo "=== Simple Quote Debug ===\n\n";

echo "📄 .env content:\n";
echo file_get_contents('.env');
echo "\n📄 .env.example content:\n";
echo file_get_contents('.env.example');

// Test quote detection manually
$sourceContent = file_get_contents('.env');
$lines = explode("\n", $sourceContent);

foreach ($lines as $lineNum => $line) {
    $line = trim($line);
    if (empty($line)) continue;
    
    echo "\nLine {$lineNum}: '{$line}'\n";
    
    if (str_contains($line, '=')) {
        $parts = explode('=', $line, 2);
        $key = trim($parts[0]);
        $value = isset($parts[1]) ? trim($parts[1]) : '';
        
        echo "  Key: {$key}\n";
        echo "  Value: {$value}\n";
        
        // Test quote detection patterns
        $hasQuotes1 = preg_match('/=\s*"/', $line);
        $hasQuotes2 = preg_match("/=\s*'/", $line);
        $hasQuotes3 = str_starts_with($value, '"') && str_ends_with($value, '"');
        
        echo "  Pattern /=\\s*\"/: " . ($hasQuotes1 ? 'YES' : 'NO') . "\n";
        echo "  Pattern /=\\s*'/: " . ($hasQuotes2 ? 'YES' : 'NO') . "\n";
        echo "  Starts/ends with quotes: " . ($hasQuotes3 ? 'YES' : 'NO') . "\n";
        
        // Test the actual condition I'm using
        $originalQuoted = preg_match('/=\s*"/', $line) || preg_match("/=\s*'/", $line);
        echo "  Should be quoted: " . ($originalQuoted ? 'YES' : 'NO') . "\n";
    }
}
