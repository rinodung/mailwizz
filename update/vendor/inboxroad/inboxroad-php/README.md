# Inboxroad Library for PHP

## Getting Started  
You will need an [inboxroad account](https://www.inboxroad.com) to get started.  
Once you get an account, you will need to [get your api key](https://www.inboxroad.com/) 
to use it in the API calls.  

## Installation

Make sure you have [composer](https://getcomposer.org) installed.

Require the package

```bash
$ composer require inboxroad/inboxroad-php
```

#### PHP Versions

Requires PHP >= 7.1  

## Usage

```php
<?php

// Include Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Create the http client, we will need it later:
$httpClient = new Inboxroad\HttpClient\HttpClient((string)getenv('INBOXROAD_API_KEY'));

// Send email, method 1: 
try {

    // Create the message object
    $message = (new Inboxroad\Models\Message())
        ->setFromEmail((string)getenv('INBOXROAD_SEND_EMAIL_FROM_EMAIL'))
        ->setToEmail((string)getenv('INBOXROAD_SEND_EMAIL_TO_EMAIL'))
        ->setToName('Inboxroad API Test')
        ->setReplyToEmail((string)getenv('INBOXROAD_SEND_EMAIL_FROM_EMAIL'))
        ->setSubject('Testing')
        ->setText('Testing...')
        ->setHtml('<strong>Testing...</strong>');
    
    // Create the endpoint connection
    $messages = new Inboxroad\Api\Messages($httpClient);
    
    // Send the message
    $response = $messages->send($message);
    
    // Get the message id
    echo $response->getMessageId() . PHP_EOL;

} catch (\Inboxroad\Exception\RequestException $e) {

  echo $e->getMessage() . PHP_EOL;
}

// Send email, method 2: 
try {

    // Create the object instance
    $inboxroad  = new Inboxroad\Inboxroad($httpClient);
    
    // Access the messages endpoint and send a message
    $response  = $inboxroad->messages()->send([
        'fromEmail'     => (string)getenv('INBOXROAD_SEND_EMAIL_FROM_EMAIL'),
        'toEmail'       => (string)getenv('INBOXROAD_SEND_EMAIL_TO_EMAIL'),
        'toName'        => 'Inboxroad API Test',
        'replyToEmail'  => (string)getenv('INBOXROAD_SEND_EMAIL_FROM_EMAIL'),
        'subject'       => 'Testing',
        'text'          => 'Testing...',
        'html'          => '<strong>Testing...</strong>'
    ]);
    
    // Get the message id
    echo $response->getMessageId() . PHP_EOL;

} catch (\Inboxroad\Exception\RequestException $e) {

    echo $e->getMessage() . PHP_EOL;
}
```

## License
MIT

## Test  
Following environment variable must be set in order to test the actual sending process:  
`INBOXROAD_API_KEY` - The API key for accessing the API  
`INBOXROAD_SEND_EMAIL_ENABLED` - Whether the tests should send emails (1 | 0)  
`INBOXROAD_SEND_EMAIL_FROM_EMAIL` - The email address from where the emails come from  
`INBOXROAD_SEND_EMAIL_TO_EMAIL` - The email address where emails will go  
Without these, the tests will run but no email will ever be sent.  

Run the tests with: 
```bash
$ composer test
``` 

## Bug Reports
Report [here](https://github.com/inboxroad/inboxroad-php/issues).
