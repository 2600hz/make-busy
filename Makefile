ROOT = $(shell readlink -f .)

.PHONY = docs docs-validate docs-report docs-setup docs-build docs-clean

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
