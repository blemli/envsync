# EnvSync Remove Feature Documentation

## Overview

The `--remove` option has been added to the `env:sync` command to safely remove keys from the source file (`.env`) that are not present in the target file (e.g., `.env.example`).

## Usage

```bash
php artisan env:sync --path=.env.example --remove
```

## Safety Features

### 1. Version Control Detection
The command automatically detects if the source file (`.env`) is version controlled:
- ✅ **Version controlled files**: Changes can be reverted if needed
- ⚠️ **Unversioned files**: Shows warning about permanent data loss

### 2. Confirmation Prompts
- **Interactive mode**: Asks for confirmation before removing keys
- **Double confirmation**: For unversioned files, requires additional confirmation
- **Force mode restrictions**: `--force` is blocked for unversioned files for safety

### 3. Clear Warnings
```
⚠️  REMOVE MODE: Keys found in '.env' but missing in '.env.example':
  EXTRA_KEY_1=value1
  EXTRA_KEY_2=value2

🚨 WARNING: '.env' is NOT version controlled!
   Removing keys from unversioned files can result in permanent data loss.
   Consider committing your changes to git before proceeding.
```

## Examples

### Interactive Remove (Recommended)
```bash
php artisan env:sync --path=.env.example --remove
```
- Shows which keys will be removed
- Asks for confirmation
- Double confirmation for unversioned files

### Force Remove (Version Controlled Files Only)
```bash
php artisan env:sync --path=.env.example --remove --force
```
- Only works if `.env` is version controlled
- Automatically removes keys without prompts
- Blocked for unversioned files for safety

## Use Cases

### 1. Cleaning Up Development Environment
Remove deprecated or unused environment variables from your `.env` file:
```bash
# After removing keys from .env.example
php artisan env:sync --path=.env.example --remove
```

### 2. Synchronizing with Team Standards
Ensure your local `.env` matches the team's `.env.example` structure:
```bash
# First sync missing keys, then remove extras
php artisan env:sync --path=.env.example
php artisan env:sync --path=.env.example --remove
```

### 3. Environment Cleanup
Remove environment-specific keys that shouldn't be in production:
```bash
php artisan env:sync --path=.env.production --remove
```

## Safety Workflow

1. **Always backup first** (or ensure version control)
2. **Review the keys** that will be removed
3. **Confirm the operation** when prompted
4. **Verify the result** after removal

## Error Handling

### Unversioned Files with --force
```
❌ Cannot use --force with --remove on unversioned files for safety reasons.
   Please version control '.env' first or run without --force for confirmation prompts.
```

### User Cancellation
```
❌ Remove operation cancelled
```

### No Keys to Remove
```
Files are already in sync. No changes needed.
```

## Implementation Details

- Uses the existing file structure preservation system
- Maintains comments and formatting
- Integrates with the existing `#ENVIGNORE` system
- Respects ignored entries in target files

## Best Practices

1. **Version Control**: Always commit your `.env` file to git before using `--remove`
2. **Review First**: Check which keys will be removed before confirming
3. **Test Environment**: Try the operation in a test environment first
4. **Backup Strategy**: Have a backup strategy for critical environment files
5. **Team Communication**: Coordinate with team when removing shared environment variables

## Command Options Summary

| Option | Description |
|--------|-------------|
| `--remove` | Enable remove mode to delete keys from source |
| `--force` | Skip confirmations (blocked for unversioned files) |
| `--path` | Specify target file to compare against |

## Integration with Existing Features

The `--remove` option works alongside existing features:
- Can be combined with regular sync operations
- Respects `#ENVIGNORE` markers in target files
- Works with version control detection
- Integrates with force mode (with safety restrictions)
