# TODO

## Good to have

2. Each test should have a brief description what is tested and why. References to unknown issue tracker are just bad.

## Important

1. Somehow indicate a dirty state after test (e.g. env should be re-created)?
2. Somehow sync with Kazoo events (e.g. voicemail stored and is available)
3. Review and add more basic test cases (e.g. call between accounts, federation?)
5. global/local resources create/use/test
6. PHP SDK lazy load needs to be explicit and more intuitive

## Urgent

2. Reset Conferences to initial state systematically
3. Conference callflow update/cache/load?
4. Reduce tone timeouts to speed up testing
5. Abstract cached entities constructors (__construct/initialize pairs)
7. When HTTP REST API fails display sane and complete error message (not a dump)
8. Make entities names TestCase-specific, e.g. 'DeviceTest Device 1'
9. list and hup test case channels only
10. have a flag to skip waiting for register

## Immediate

5. Slow number creation, need to investigate

## Bugs

1. Language bs is not found (voicemail tests, trying to say user name), therefore username prompt isn't tested.

## Leftovers

1. TODO: stub code right now, need to add remove ACL to SDK