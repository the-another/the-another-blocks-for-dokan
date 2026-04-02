.PHONY: install require update dump-autoload lint format test test-unit test-integration test-coverage docker-build docker-run docker-shell all clean

# Docker image name
DOCKER_IMAGE = another-blocks-dokan-runner:latest
DOCKER_RUN = docker run --rm -v $(PWD):/app -w /app $(DOCKER_IMAGE)

# Build Docker image
docker-build:
	docker build -t $(DOCKER_IMAGE) .

# Install composer dependencies without dev dependencies (runs in Docker)
install: docker-build
	$(DOCKER_RUN) composer install --no-dev

# Require new composer package (runs in Docker)
# Usage: make require PACKAGE="vendor/package"
require: docker-build
	$(DOCKER_RUN) composer require $(PACKAGE)

# Update composer dependencies (runs in Docker)
update: docker-build
	$(DOCKER_RUN) composer update

# Dump autoloader without dev dependencies (runs in Docker)
dump-autoload: docker-build
	$(DOCKER_RUN) composer dump-autoload --no-dev --optimize

# Run PHPCS linter in isolated environment (never touches source)
lint:
	@echo "Running linter in isolated environment..."
	@./scripts/run-isolated.sh ./vendor/bin/phpcs --standard=.phpcs.xml.dist

# Format code using PHPCBF (WARNING: This MODIFIES source code, runs in Docker)
format: docker-build
	$(DOCKER_RUN) composer phpcbf

# Run PHPUnit tests in isolated environment (never touches source)
test:
	@echo "Running tests in isolated environment..."
	@./scripts/run-isolated.sh php ./vendor/bin/phpunit

# Run unit tests only in isolated environment
test-unit:
	@echo "Running unit tests in isolated environment..."
	@./scripts/run-isolated.sh php ./vendor/bin/phpunit --testsuite=Unit

# Run integration tests only in isolated environment
test-integration:
	@echo "Running integration tests in isolated environment..."
	@./scripts/run-isolated.sh php ./vendor/bin/phpunit --testsuite=Integration

# Run tests with coverage in isolated environment
test-coverage:
	@echo "Running tests with coverage in isolated environment..."
	@./scripts/run-isolated.sh php ./vendor/bin/phpunit --coverage-html coverage

# Run Docker container interactively
docker-run:
	docker run -it --rm -v $(PWD):/app $(DOCKER_IMAGE)

# Open shell in Docker container
docker-shell:
	docker run -it --rm -v $(PWD):/app $(DOCKER_IMAGE) sh

# Run all: install, lint, test (lint and test run in isolated environment)
all: install lint test

# Clean vendor, dependencies, and build files
clean:
	rm -rf vendor/ dependencies/ composer.lock build/

