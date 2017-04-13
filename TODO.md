# TODO

## Urgent

1. List and hup test case channels only
2. Abstract cached entities constructors (`__construct/initialize` function pairs)
3. Create a FreeSWITCH language with tones (to detect what say is saying, numbers, dates).
    - Is it possible to detect sequence of tones?

## Important

1. Sync with Kazoo events (e.g. a voice mail is stored and available to retrieve)
    - now relying on timeouts, what is bad
2. PHP SDK lazy load needs to be explicit and more intuitive

## Leftovers

1. TODO: stub code right now, need to add remove ACL to SDK

## Good to have

1. Each test should have a brief description what is tested and why. References to unknown issue tracker are just bad.
2. Reduce tone timeouts to speed up testing
