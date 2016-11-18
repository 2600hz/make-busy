# TODO

## Good to have

1. Run makebusy docker without apache (with php -S), and under user rights

## Important

1. Somehow indicate a dirty state after test (e.g. env should be re-created)?
2. Somehow sync with Kazoo events (e.g. voicemail stored and is available)
3. Review and add more basic test cases (e.g. call between accounts, federation?)
4. Create binary docker images and publish them to Docker Hub
5. global/local resources create/use/test

## Urgent

1. Reset Voicemailbox to initial state systematically
2. Reset Conferences to initial state systematically
3. Conference callflow update/cache/load?

## Immediate

1. Add Trunkstore entities to account
2. Add user's device as user method createDevice (instead of account)
3. Rename KazooGateways to reflect the purpose (load Kazoo's data for account)
4. Bind systemconfigs to account

## Bugs

1. Language bs is not found (voicemail tests, trying to say user name), therefore username prompt isn't tested.
