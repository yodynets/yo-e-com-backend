PHP := php
PHPSTAN := $(PHP) vendor/bin/phpstan

PHPSTAN_MEMORY_LIMIT := 512M
PACKAGE_DIR := packages/yeod/commerce-lifecycle

.PHONY: analyse-clp analyse-clp-package analyse-clp-src analyse-clp-tests composer-da

composer-da:
	composer dump-autoload --optimize --strict-psr

test-clp:
	php artisan test  -c $(PACKAGE_DIR)/phpunit.xml.dist

## Run static analysis for the whole package
analyse-clp: analyse-clp-package

## Run static analysis for all package files
analyse-clp-package:
	$(PHPSTAN) analyse $(PACKAGE_DIR) --memory-limit=$(PHPSTAN_MEMORY_LIMIT)

## Analyse production source code only
analyse-clp-src:
	$(PHPSTAN) analyse $(PACKAGE_DIR)/src --memory-limit=$(PHPSTAN_MEMORY_LIMIT)

## Analyse tests only
analyse-clp-tests:
	$(PHPSTAN) analyse $(PACKAGE_DIR)/tests --memory-limit=$(PHPSTAN_MEMORY_LIMIT)