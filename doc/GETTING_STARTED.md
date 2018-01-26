# Getting Started - The Long Guide

The aim here is to take a relatively vanilla system and get MakeBusy and Kazoo running together.

## Clone the repo

```bash
git clone https://github.com/2600hz/make-busy
cd make-busy
```

## Composer Update

Upgrade composer and then update vendor libraries

```bash
./composer self-update
./composer update
```

Create an OAuth token with your github user (with no scopes added) to ensure speedy fetching.

## Clone the test suites

I like to add them as git submodules personally, though nothing requires it

```bash
git submodule add --name Callflow https\://github.com/2600hz/make-busy-callflow.git tests/Callflow
git submodule add --name Conference git\@github.com\:2600hz/make-busy-conference.git tests/Conference
```
