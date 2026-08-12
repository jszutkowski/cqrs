DC := docker compose
# No services needed: static analysis and style never touch the database.
PHP := $(DC) run --rm --no-deps php
# Database up; phpunit.dist.xml forces APP_ENV=test itself, so no override here.
PHP_DB := $(DC) run --rm php
# Console commands have no phpunit to force the environment, so they say it.
PHP_TEST_CONSOLE := $(DC) run --rm -e APP_ENV=test php

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
	$(PHP_TEST_CONSOLE) php bin/console doctrine:database:create --if-not-exists
	$(PHP_TEST_CONSOLE) php bin/console doctrine:migrations:migrate --no-interaction

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
	$(PHP_TEST_CONSOLE) php bin/console doctrine:database:drop --force --if-exists
	$(MAKE) install

test: ## Run every test suite
	$(PHP_DB) composer test

test-unit: ## Run the unit suite only (no database needed)
	$(PHP) composer test:unit

test-integration: ## Run the integration suite
	$(PHP_DB) composer test:integration

test-functional: ## Run the functional API suite
	$(PHP_DB) composer test:functional

coverage: ## Run tests with an HTML coverage report in var/coverage
	$(PHP_DB) composer coverage

cs: ## Check the code style
	$(PHP) composer cs

cs-fix: ## Fix the code style
	$(PHP) composer cs:fix

stan: ## Run static analysis
	$(PHP) composer stan

arch: ## Verify the architecture rules
	$(PHP) composer arch

check: cs stan arch test ## Everything CI runs
