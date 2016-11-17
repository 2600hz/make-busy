# MakeBusy

## About

MakeBusy is a functional test suite for Kazoo. It works by creating test accounts in specified Kazoo cluster using Kazoo HTTP REST API and
performing test calls to Kazoo cluster with separate automated FreeSwitch instances. Kazoo entities are used to store arbitrary information
required for testing and generation of FreeSwitch configuration.

## Components

To run tests you'll requite one Makebusy instance serving XML configs for automated FreeSwitch instances via HTTP, and at least 3
FreeSwitch instances (to act as device, carrier and pbx substitutes).

## FreeSwitch automation

FreeSwitch instances are automated by providing generated XML configs for SIP endpoints (acting as device, carrier or external pbx),
and by issuing commands to FreeSwitch control socket (port 8021 usually). Therefore FreeSwitch and Makebusy instances must be visible
to each other (tcp port 8021 and 80), and in addition, FreeSwitch instances must have SIP and RTP access to Kazoo cluster, and Makebusy
must have access to Kazoo REST HTTP API.

## Docker images

MakeBusy comprises of 4 Docker images: makebusy, makebusy-fs-auth, makebusy-fs-pbx and makebusy-fs-carrier, where makebusy-fs-* are
automated FreeSwitch images (what, in turn, are based on kazoo/freeswitch docker image). Please see [Docker HOWTO](docker/README.md).

## How to write tests

Please see a brief (but yet complete) [HOWTO](doc/HOWTO.md).

## How to run tests

### File structure

Tests are supposed to reside in tests/KazooTests/Applications folder, grouped by application (like Callflow) to test.
Each test file is supposed to test one exact feature.

### TestCase setup and caching

Each defined TestCase defines a number of Kazoo entities. When a test file is run it checks the Kazoo for entities
to be defined first, starting from TestAccount, and if they are, the test environment will be loaded from Kazoo,
instead of creating it. You can alter this by defining shell enviroment variable CLEAN.

### Environemt variables

Clear (and re-create) Kazoo config before running test, FreeSwitch profile is also restarted:
```
CLEAN=1 ./run-test path_to_test.php
```

(Re)Register existing profile before running tests:
```
REGISTER_PROFILE=1 ./run-test path_to_test.php
```

Restart existing profile before running tests:
```
RESTART_PROFILE=1 ./run-test path_to_test.php
```

Dump FreeSwitch events content to MakeBusy log file:
```
DUMP_EVENTS=1 ./run-test path_to_test.php
```

Log messages to console also:
```
LOG_CONSOLE=1 ./run-test path_to_test.php
```

## Intended workflow

1. Define and name the TestCase
2. Define and name the test
3. Do LOG_CONSOLE=1 ./run-test path_to_test.php, and see what's going on
4. Ensure newly defined test can run successfuly in sequential calls and it cleanups after itself
5. Ensure newly defined test can run successfuly in freshly created environment: CLEAN=1 LOG_CONSOLE=1 ./run-test path_to_test.php
6. Have a cup of coffee, go to 2. or 1.

## Configuration file

A valid configuration file config.json must exist in MakeBusy root folder (see etc/config.json as example).

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
