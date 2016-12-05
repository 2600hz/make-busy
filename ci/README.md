# MakeBusy CI

1. Host receives a PR with commit ref
2. Build Kazoo image against this ref
3. Start a copy of Kazoo cluster with this ref in separate network segment (ref)
4. Start a copy of MakeBusy instances in the same network segment (ref)
5. Run specified test suite
6. Report results back required: suite log file, repo, owner, commit, github-access-key

# Unclear

1. How to indicate full rebuild? (e.g. build fails)
2. How to indicate to re-use/re-create couchdb settings?

