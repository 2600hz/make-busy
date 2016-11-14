## Indention/Whitespace.
 * use an indention of 4 spaces, with no tabs. 
 * Avoid excessive whitespace, no more than a single line of white space should be used to create separation between function names or inside functions. 
 * Functions should have opening curly braces on the same line as the function name EG: 
```php
public function testSomething() {
}
```
 * Classses should have the opening curly brace on the next line EG: 

```php
class TestSomething extends CallFlowTest
{
}
```

## Name formatting:
 * Class names should use UpperCamelCase with an uppercase first leter of each word.
 * Method (function) names should use lowerCamelCase with an uppercase first letter of each word except the first. 
 * Variables/Property names which contain multiple words should use snake_case. 
 * Constants should be ALL\_UPPER\_CASE
 * null should always be completely lowercase 
 * TRUE and FALSE should always be UPPERCASE. 

## Naming conventions:

###Uniform test names:  
Php unit supports filters based on a regex pattern match of the test name, therefore, the uniformity of names of tests is vitally important. Names should use the convention 'test{Feature or functionality to be tested}{Brief (one to two word) description of test to be executed}' Since the class this is in is DeviceTests, we dont need to use the word "device" in the test function name, since we can filter for devices by matching class name pattern. 
    
    ```
    Example: For a test of the device call forwarding functionality of enabling forwarding, 
    we could use testCfEnable. Then for testing the keypress feature, we could name the test
    testCfKeypress. 
    
    This would allow us to filter all the device CF tests on devices using the pattern DeviceTests::testCf.
    
    ``` 
####Test Naming convention:     

    * Test names should be formatted as test<Funcitonality><Type>.
    * Test names do not need to contain the class names of the application being tested.
    
NOTE: I am still fixing this for the few exceptions that exist currently in deviceTest.   

####Uniform naming of entities involved in tests: 
 
The call legs involved in a test should be referred using an alphabetical prefix and this naming should be consistent throughout each test. This gets a bit more complicated when our test requires devices which reside under a user. In this case, we would use a\_user for the users name, and a\_device\_1, a\_device\_2 for the devices under this user. 


#### Examples:
```
a_<entity type> - The device/user/channel involved in originating calls. 
    a_device          - The device which is the A leg (originator) in the test calls.
    a_device_id       - The id of a_device. 
    a_device_username - the sip username of a_device.
    a_user            - The user which is the A leg of the call.
    a_device_1        - The first device assigned to a a_user.
    a_device_2        - The second device assigned to a_user
    a_device_1_id     - The ID of device 1 assigned to a_user. 
    a_voicemail_box   - The voicemail box assigned to a_user. 
    a_channel         - The channel created for the A leg of the call from a_device. 
    a_channel_1       - The first channel used in a call from a_user, a_device_1. 
b_<entity type> - The device/user/channel involved in recieving a call. 
    b_device          - The device which is the B leg for the test calls in the test class. 
    b_device_id       - The id of b_device. 
    b_device_username - the sip username of b_device.
    b_user            - The user which is the B leg (destination) of the calls.
    b_device_1        - The first device assigned to b_user.
    b_device_2        - The second device assigned to b_user
    b_device_1_id     - The ID of device 1 assigned to b_user. 
    b_voicemail_box   - The voicemail box assigned to b_user. 
    b_channel         - The channel created for the B leg of the call from b_device. 
    b_channel_1       - The first channel used in a call from b_user, b_device_1. 
    b_voicemail_box   - The voicemail box assigned to b_user. 
c_<entity type> - The second entity involved in recieving a call (EG: forwarding destination). 
    c_device          - The device which is the C leg for the test calls in the test class. 
    c_device_id       - The id of c_device. 
    c_device_username - the sip username of c_device.
    c_user            - The user which is the C leg (destination) of the calls.
    c_device_1        - The first device assigned to c_user.
    c_device_2        - The second device assigned to c_user
    c_device_1_id     - The ID of device 1 assigned to c_user. 
    c_voicemail_box   - The voicemail box assigned to c_user. 
    c_channel         - The channel created for the C leg of the call from c_device. 
    c_channel_1       - The first channel used in a call from c_user, c_device_1. 
    c_voicemail_box   - The voicemail box assigned to c_user.      

```
NOTE: We are removing a\_leg from this list as it is ambigious. We should use a\_device for devices, a\_user for users etc..

This makes it really easy to describe the test in plain english and this is how I will be writing the new Jira tickets as well. 

```
 Example: 
     A_device calls B_user and upon no answer is redirected to B_voicemail,
     A_device leaves a message, 
     C_device calls into B_voicemail,
     C_device logs in using B_voicemail pin code, 
     C_device is able to retrieve B_users message left by A_device. 
```

## Basic test best practices: 
 * Avoid global FreeSWITCH ESL commands: 
     * Whenever possible, avoid the use of globally affecting FreeSWITCH ESL commands such as rescan and hupall.         
     * When these absolutely must be used to fulfill a requirement of a test, please add a //TODO: comment so this usage can be later addressed when we solve the need for these with better support on the backend of the tools. 
 * Test against both proxies. 
     * Tests calls should happen inside a foreach loop of self::getSipTargets so that tests will be performed against all Kamailio proxies configured. 
         NOTE: This is only applicable for tests which can viably use this, such as tests where a result should be the same regardless of the number of times tested. Tests like voicemail setup are ok if they just use a random proxy.
 * Avoiding test interdependency. Tests should be discrete (some DeviceTests need the fixed for this as well). 
     * No test should be dependent on previous configurations from other tests. 
         * Each test should make any configuration changes required for that tests test environemnt.
         * Whenever possible, tests should not depend on results of previous test
         * The only exception here is the initial configuration of the entities to be tested, which should always be done in the setUp/setUpBeforeClass functions at the top of the test class. 
     * Each test should leave the environment in the same state if found it: 
         * Each tests that changes a parameter on an entity should change it back at the end of the test. (add a reset method to the Kazoo/Applications/<app_name>.php to accomplish this). 
         * Test channels should always be hung up at the end of calls, if the expected result is for a call to complete. 
