ROOT = $(shell readlink -f .)

.PHONY = docs docs-validate docs-report docs-setup docs-build docs-clean \
	fmt set-defaults

DOCS_ROOT=$(ROOT)/doc/mkdocs
docs: docs-validate docs-report docs-setup docs-build

docs-validate:

docs-report:
	@$(ROOT)/bin/reconcile_docs_to_index.bash

docs-setup:
	@$(ROOT)/bin/validate_mkdocs.py
	@$(ROOT)/bin/setup_docs.bash
	@mkdir -p $(DOCS_ROOT)/theme $(DOCS_ROOT)/docs $(DOCS_ROOT)/site

docs-build:
	@$(MAKE) -C $(DOCS_ROOT) DOCS_ROOT=$(DOCS_ROOT) docs-build

docs-clean:
	@$(MAKE) -C $(DOCS_ROOT) DOCS_ROOT=$(DOCS_ROOT) clean

docs-serve: docs-setup docs-build
	@$(MAKE) -C $(DOCS_ROOT) DOCS_ROOT=$(DOCS_ROOT) docs-serve

TESTS := $(wildcard tests/*) src/
CHANGED ?= $(foreach test,$(TESTS),$(shell git --no-pager diff --name-only HEAD origin/master -- *.erl))

FORMATTER ?= ./vendor/bin/phpcbf
CHECKER ?= ./vendor/bin/phpcs

fmt: $(FORMATTER)
	@$(FORMATTER) $(CHANGED)

$(FORMATTER):
	@$(ROOT)/composer update
	@$(MAKE) set-defaults

set-defaults:
	@$(CHECKER) --config-set default_standard PSR1
	@$(CHECKER) --config-set report_format summary
	@$(CHECKER) --config-set colors 1
	@$(CHECKER) --config-set tab_width 4
