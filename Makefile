DC := docker compose
PHP := $(DC) run --rm --no-deps php
PHP_DB := $(DC) run --rm -e APP_ENV=test php

.DEFAULT_GOAL := help

.PHONY: help install up down logs shell db-reset test test-unit test-integration test-functional coverage cs cs-fix stan arch check

help: ## Show the available targets
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'

install: ## Build the image, install dependencies and prepare both databases
	$(DC) build php
	$(PHP) composer install --no-interaction
	$(DC) up -d mysql rabbitmq redis
	$(DC) run --rm php php bin/console lexik:jwt:generate-keypair --skip-if-exists
	$(DC) run --rm -e APP_ENV=dev php php bin/console doctrine:database:create --if-not-exists
	$(DC) run --rm -e APP_ENV=dev php php bin/console doctrine:migrations:migrate --no-interaction
	$(PHP_DB) php bin/console doctrine:database:create --if-not-exists
	$(PHP_DB) php bin/console doctrine:migrations:migrate --no-interaction

up: ## Start the whole stack (API, workers, frontend, websockets)
	$(DC) up -d

down: ## Stop the stack
	$(DC) down

logs: ## Follow the logs of every service
	$(DC) logs -f

shell: ## Open a shell in the PHP container
	$(DC) run --rm php bash

db-reset: ## Drop and rebuild both databases
	$(DC) run --rm -e APP_ENV=dev php php bin/console doctrine:database:drop --force --if-exists
	$(PHP_DB) php bin/console doctrine:database:drop --force --if-exists
	$(MAKE) install

test: ## Run every test suite
	$(PHP_DB) vendor/bin/phpunit

test-unit: ## Run the unit suite only (no database needed)
	$(PHP) vendor/bin/phpunit --testsuite=Unit

test-integration: ## Run the integration suite
	$(PHP_DB) vendor/bin/phpunit --testsuite=Integration

test-functional: ## Run the functional API suite
	$(PHP_DB) vendor/bin/phpunit --testsuite=Functional

coverage: ## Run tests with an HTML coverage report in var/coverage
	$(PHP_DB) vendor/bin/phpunit --coverage-html var/coverage

cs: ## Check the code style
	$(PHP) vendor/bin/php-cs-fixer fix --dry-run --diff

cs-fix: ## Fix the code style
	$(PHP) vendor/bin/php-cs-fixer fix

stan: ## Run static analysis
	$(PHP) vendor/bin/phpstan analyse --no-progress

arch: ## Verify the architecture rules
	$(PHP) vendor/bin/phparkitect check

check: cs stan arch test ## Everything CI runs
