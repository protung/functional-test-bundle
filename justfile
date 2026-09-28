# List the available recipes
default:
    @just --list

# Run all checks, like CI does
check: composer-check cs-check analyse test

# Validate composer.json and check that it is normalized
composer-check:
    composer validate
    composer normalize --dry-run

# Check the coding standard
cs-check:
    vendor/bin/phpcs -p

# Fix coding standard violations
cs-fix:
    vendor/bin/phpcbf -p

# Run the static analysis
analyse:
    vendor/bin/phpstan analyse --memory-limit=256M

# Regenerate the PHPStan baseline
analyse-baseline:
    vendor/bin/phpstan analyse --memory-limit=256M --generate-baseline

# Run the tests; extra arguments are passed to PHPUnit
test *args:
    vendor/bin/phpunit {{ args }}
