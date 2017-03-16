ROOT = $(shell readlink -f .)
DOCS_ROOT=$(ROOT)/doc/mkdocs
docs: docs-report docs-setup docs-build

docs-report:
	@$(ROOT)/bin/reconcile_docs_to_index.bash

docs-setup:
	@$(ROOT)/bin/validate_mkdocs.py
	@$(ROOT)/bin/setup_docs.bash
	@cp $(DOCS_ROOT)/mkdocs.yml $(DOCS_ROOT)/mkdocs.local.yml
	@mkdir -p $(DOCS_ROOT)/theme

docs-build:
	@echo "\ntheme: null\ntheme_dir: '$(DOCS_ROOT)/theme'\ndocs_dir: '$(DOCS_ROOT)/docs'\n" >> $(DOCS_ROOT)/mkdocs.local.yml
	@mkdocs build -f $(DOCS_ROOT)/mkdocs.local.yml --clean -q --site-dir $(DOCS_ROOT)/site

docs-clean:
	@rm -rf $(DOCS_ROOT)/site $(DOCS_ROOT)/docs $(DOCS_ROOT)/mkdocs.local.yml

docs-serve: docs-build
	@mkdocs serve --dev-addr=0.0.0.0:9876 -f $(DOCS_ROOT)/mkdocs.local.yml
