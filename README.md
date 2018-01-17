# MakeBusy

## About

MakeBusy is a functional test suite for Kazoo. It works by creating test accounts in specified Kazoo cluster using Kazoo HTTP REST API and
performing test calls to Kazoo cluster with separate automated FreeSWITCH instances. Kazoo entities are used to store arbitrary information
required for testing and generation of FreeSWITCH configuration.

## Requirements

Makebusy makes extensive use of FreeSWITCH ESL and requires the use of the esl php module

check https://github.com/lazedo/freeswitch-php-esl for HowTo install
 
## Components

To run tests you'll requite one MakeBusy instance serving XML configs for automated FreeSWITCH instances via HTTP, and at least 3
FreeSWITCH instances (to act as device, carrier and pbx substitutes).

## FreeSWITCH drones

FreeSWITCH drone instances acting as device, carrier or external pbx are automated by providing generated XML configs for SIP gateways,
and by issuing commands to FreeSWITCH control socket (port 8021 usually). Therefore FreeSWITCH and MakeBusy instances must be visible
to each other (TCP port 8021 and 80), and, in addition, FreeSWITCH instances must have SIP and RTP access to Kazoo cluster, and MakeBusy
must have access to Kazoo REST HTTP API.

## Docker images

MakeBusy comprises of 4 Docker images: makebusy, makebusy-fs-auth, makebusy-fs-pbx and makebusy-fs-carrier, where makebusy-fs-* are
automated FreeSWITCH images (what, in turn, are based on kazoo/FreeSWITCH docker image). Please see [Docker HOWTO](docker/README.md).

## How to write tests

Please see a brief (but yet complete) [HOWTO](doc/HOWTO.md).

## How to run tests

### File structure

Tests are supposed to reside in tests/KazooTests/Applications folder, grouped by Kazoo application (like Callflow) they rely on.
Each test file is supposed to test one exact feature.

### TestCase setup and caching

All test cases must descend from TestCase class. Each test case defines common setup for number of tests.
This common setup may or may not include several Kazoo entities, such as Devices,
Carriers, Callflows, Voicemails and so on. MakeBusy tries hard to reuse Kazoo entities by caching (as it takes time to create them).
It is possible to disable caching by setting environment variable CLEAN, see below.

### Environment variables

Don't cache Kazoo entities:
```
CLEAN=1 ./run-test path_to_test.php
```

Hang up all drone channels before running test:
```
HUPALL=1 ./run-test path_to_test.php
```

Restart existing FreeSWITCH sofia profile before running test:
```
RESTART_PROFILE=1 ./run-test path_to_test.php
```

Skip gateway registration:
```
SKIP_REGISTER=1 ./run-test path_to_test.php
```

Skip test account creation:
```
SKIP_ACCOUNT=1 ./run-test path_to_test.php
```

Dump FreeSWITCH events content to MakeBusy log file:
```
DUMP_EVENTS=1 ./run-test path_to_test.php
```

Log HTTP request and response bodies:
```
LOG_ENTITIES=1 ./run-test path_to_test.php
```

Replace certain variables in HTTP response (for documentation purposes, to save space, etc):
```
SKIP_SOME_RESPONSE_VARS=1 ./run-test path_to_test.php
```

Log debug messages to console:
```
LOG_CONSOLE=1 ./run-test path_to_test.php
```

Display stack trace on test error/failure:
```
STACK_TRACE=1 ./run-test path_to_test.php
```

Override config.json values to connect to Kazoo:
```
KAZOO_URI="user password realm uri" ./run-test path_to_test.php
```

## Intended workflow

1. Define and name test case
2. Define and name test
3. Do `LOG_CONSOLE=1 ./run-test path_to_test.php` and see what's going on
4. Ensure newly defined test can run successfully in sequential calls and it cleanups after itself
5. Ensure newly defined test can run successfully in freshly created environment: `CLEAN=1 LOG_CONSOLE=1 ./run-test path_to_test.php`
6. Have a cup of coffee, go to 2. or 1.

## Configuration file

A valid configuration file config.json must exist in MakeBusy root folder (see [the sample config](./etc/config.json.dist) as example).

## ASCII art
```
           `
           .'.``
           ,...`
           .'...`         ``
            ;,@#.       ;;'';:`
            `,,#,`     ;;,,,:;:.`
             ',,+.   `';::  ;:':`
             `::+,` ,;:,,; : ,,':.
              +.+'.;;,:` ::` ,,,;,.`
             ..:'+;'`:..,:,;:,,,,;:.
              :,'';`,: ;.. .,,,,,,;:.
               ,':``:,:,;`:.,.:,,.,;:`
              ;',:`:,,. ,,.: :`,,,,,',`
             ';::.`:,.`::,,.; ,,:,,:,;.`
            ;:,:: ;,.,;,`:.:`:; `,:..::`
           `;,: ;:`.`.,,.,`;.,;``::.,,;,`
           .:,: .:;`:.,:```,:,` ,``:`,,:.
           +;,:`  :,.:`:;:,`,:,..:`;:,,:,`
           #;:::';,,:  `,, ,`;:,``;: ,,,:,`
           #,':,,,,,`' ;::' ;, :,:;,`,.,:,.
           `;,',,,,,: :.,`,,`.. ,,  `:,,:;,`
            +,,;,::.:,`;`,,:'..,`,,.:,,::;#.
            `#,.',,,,,: ,; .,;, :`:,,,,:;;,,`
           ` .+:.'::`:::,., :,:: :.,:,:;;;,;.
              ;::,;,:.,::` ;,.:,;,.::;:;;;;'.
               +:,,;,,,.::,.' :,:,#.;:;;;;#::`
               `#:,,;:,:`,: .`:,::,;;;;;##+::.
                `#:::;::,,,;..,,,,:;;;'#+#'';,
                 `#:::,',:,.,,,,,;;;;++#@'+;;:`
                  .#;;:,';:,,:::;:;;+++#+;+++;.
                   .+'::::;;;;;::;:+++#+'''':',`
                   ``++::::;:.,:::++++++';';';'.
                     `;+;:;;;::,:'+###++;;.+'':'`
                      `:+';;;;::.,+,:+++:,:,#''',`
                       `.++;;;;;':;'++'::::,.';:',`
                         .:+#;;:;++'+++#,,..,,'+;;.`
                          `.;+#;:++++#+++.`.:,,#;;+,`
                            `.:#+:'+++++++.. ,,:++;'.
                              `.:#;'+++#++#. .,,:';''.`
                ,`              .,++:;++##+#,` ::;;''',`
                ::.               `:@:'+#+++#,:`:,;':++:`
                :,.                `,#:'+##+'#,, ..;';''+++`
                .;,`                `,#;'##'+'+,, :,;;;;;'''#`
                 `,.                 `,@;'+##+'+,,,,:;;;'''+'#.
                 ,@,`                 `:#;'+#++;+.,;''';';;;'''':`
                ` ::`                  `:+;'+++#'';';';+#;'''';'';`
                 .;''+``             `  `''+'#++'''''''';;;;''';+',
                '':;;;'.`           .'++''';+'#++''''';;'';''';+'''.
                .';;:;:',`        '@.+#####+:'+#++'''''@;@;+''''''''.`
   ,``           ';;:;;;'.`      @+######+##@'+#+++';;''+;#;;;;'''';'.
    ..`          ';::;;';:`      :#+#'+##''++'+#+++'';'+#`'''';''';'';`
    ::.          ';;;;';::`     '#+#+#''#'+'#'+##++'';''`@ +;;''''+#+',`
   ``,..         ,';;';;;,`     +'+@''#;@+';:#+#'##+++''':+;''''''+#@':`
     `.#.        `'';;;;,`       #:;#:@;#+;:+#;'##+++''+'';';'''++##+';.
      :`:;':.     ',';;,`        #::;::#+::.'+;'##+++++''''''''++##+'+;,`
      ``';';;;;`  :;;;,`        `;'##+#+:,`:#:+;+##++++''''''++###+'',',`
      .#+'';'''#` `';;.          #,,,+;:,` ',,#;;###++++++'++++##++;:,+,`
       @++';''##,` ':',           +++::,` `+:.`';'#'#+++++++++++#+;:,:#,`
       `++'';'++:` +;',`          `.`,.`  ',,``#;'+##++++++++++++:;:::;,`
        ++'''#';,` .';:`           ```    ;..` .+''+###++++++++#;'+;:#:.
        `@+++##;.   ':;.                  +,.  `#;''+###++@'+++;;'#+#;:`
         .++#';.`   ':+,                `+.,`   `#:;;'#+:;@+++,;#+++;:.
         `@++#:`    ;;+,`               :++:`    .@:;;'+,'''';:#++++@,`
           #'+;`    .';:`               #+#:`   ``:#:;;;;'';:::;+++++,.
           .#;#.    `;+:`             `,#+;,`     `,#:';;':::::;+;++'++;.',.'`
           `.#:@.`  `.+,`             `#++:.       `,#;:;,::::+#;:,:;;++''++'''#.``
           ``;#;#.    +,`              #+;,`         ,;#';:;+#';,.`.,;';;+;,;::;'.
             `'+;;`   `;`             :`::.           `,'+';;;:,`    `,,,,,...'::@.
              `##',`   :,`            '',``            `.,+',,.`      ``````#;,,.#.`
               `@#+,   `;:`          +',.`               `.+';#'.` `  `;++:...,``#.`
               `.##;`   `.+.`      `#+:,`                  `:+.`.```.``...`..```,..`
                ``,;'`   `.+`     ,@+,:,`                   `.@,````````````  ` '..
                  `.`..,,:;;+#'',`.+::.`                     ``#:`         ` ` @`.`
                     ``.`...,,++#',,,.`                        .,#,           @`.`
                            ``.,,.,.``                        ` ``,@,`       +`.`
                               ````                               `..+#'``.#`..`
                                                                    ``......`.`
                                                                      ````..`
```
