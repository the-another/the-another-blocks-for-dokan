# Testing and Linting with Source Isolation

This project implements **isolated testing** to ensure that running tests and linters **never modifies the source code**.

## How It Works

When you run tests or linting, the following happens automatically:

1. **Creates a temporary directory** for isolated testing
2. **Copies source code** to the temporary directory (excluding `vendor/`, `node_modules/`, etc.)
3. **Runs `composer install`** in the isolated directory
4. **Executes tests/linter** in the isolated environment
5. **Cleans up** the temporary directory when done

Your source code remains **completely untouched** throughout this process.

## Available Commands

### Testing

```bash
# Run all tests (in isolated environment)
make test
composer test

# Run unit tests only
make test-unit
composer test:unit

# Run integration tests only
make test-integration
composer test:integration

# Run tests with coverage
make test-coverage
composer test:coverage
```

### Linting

```bash
# Run linter (in isolated environment - never touches source)
make lint
composer lint

# Format code (WARNING: This MODIFIES source code)
make format
composer lint-fix
```

### Complete Workflow

```bash
# Run everything: install, build, lint, test
make all
```

## Technical Details

### The Isolation Script

The core isolation logic is in `scripts/run-isolated.sh`. This script:

- Creates a temporary directory using `mktemp`
- Uses `rsync` to copy source files (with exclusions)
- Installs dependencies in the isolated copy
- Executes the requested command
- Returns the exit code
- Automatically cleans up on exit

### What Gets Excluded

The following are excluded from the isolated copy:

- `vendor/` - Dependencies (reinstalled fresh)
- `node_modules/` - Node dependencies
- `build/` - Build artifacts
- `.git/` - Git repository
- `.phpunit.cache/` - PHPUnit cache
- `coverage/` - Coverage reports
- `composer.lock` - Lock file
- `.idea/` - IDE settings
- `*.zip` - Package files

### Why This Matters

**Without isolation:**
- Running `composer install` during tests would modify your source `vendor/` directory
- Test artifacts could pollute your working directory
- Parallel test runs could interfere with each other
- Development and CI environments behave differently

**With isolation:**
- Source code is guaranteed to remain pristine
- Tests run in a clean environment every time
- Multiple test runs can happen concurrently without conflicts
- Consistent behavior across all environments

## CI/CD Integration

The isolation pattern works seamlessly in CI/CD pipelines:

```yaml
# Example GitHub Actions
- name: Run tests
  run: make test

- name: Run linter
  run: make lint
```

Since the isolation script handles all the setup, you don't need special CI configuration.

## Development Workflow

### Normal Development
```bash
# Install dependencies (this DOES modify source)
composer install

# Run tests (isolated - never touches source)
make test

# Run linter (isolated - never touches source)
make lint

# Fix code style issues (this DOES modify source)
make format
```

### Before Committing
```bash
# Verify everything passes without modifying source
make lint
make test
```

## Troubleshooting

### Script Permission Denied

If you get a permission error:
```bash
chmod +x scripts/run-isolated.sh
```

### Slow Test Runs

The isolation process adds overhead for copying files and installing dependencies. This is the trade-off for guaranteed source isolation. However:

- First run installs dependencies (slower)
- Subsequent runs still need to copy and install (consistent time)
- The overhead is typically 5-15 seconds depending on project size

### Temporary Directory Cleanup

The script automatically cleans up temporary directories. If cleanup fails (e.g., process killed), you can manually remove them:

```bash
# List temporary test directories
ls -la /tmp | grep plugin-test

# Remove them
rm -rf /tmp/plugin-test.*
```

## Notes

- **`make format`** and **`composer lint-fix`** intentionally modify source code (that's their purpose)
- All other test/lint commands guarantee source isolation
- The isolation script uses POSIX shell (`sh`) for maximum compatibility
- Temporary directories are created in the system temp directory (`/tmp` on Unix-like systems)
