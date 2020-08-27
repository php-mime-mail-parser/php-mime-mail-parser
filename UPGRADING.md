PhpMimeMailParser Upgrade Guide
====================

6.0 to 7.0
----------

In order to take advantage of the new features of PHP, PhpMimeMailParser dropped the support
of PHP 7.2. The minimum supported PHP version is now PHP 7.3. Type hints and return
types for functions and methods have been added wherever possible. 

=>By default better DX
=> we solved all the issues

### Constructor

Specify a file path

```php
require_once __DIR__.'/vendor/autoload.php';

// Before...
$parser = new PhpMimeMailParser\Parser();
$parser->setPath('path/to/email.eml'); 

// After
$parser = PhpMimeMailParser\Parser::fromPath('path/to/email.eml'); 
```

Specify the raw mime mail text

```php
require_once __DIR__.'/vendor/autoload.php';

// Before...
$parser = new PhpMimeMailParser\Parser();
$parser->setText('... raw email ...'); 

// After
$parser = PhpMimeMailParser\Parser::fromText('... raw email ...'); 
```

Specify a stream to work with mail server

```php
require_once __DIR__.'/vendor/autoload.php';

// Before...
$parser = new PhpMimeMailParser\Parser();
$parser->setStream(fopen("php://stdin", "r")); 

// After
$parser = PhpMimeMailParser\Parser::fromStream(fopen("php://stdin", "r"));
```



### Get the metadata of the message

Get the sender and the receiver
```php
// Before
$to = $parser->getHeader('to');
$from = $parser->getHeader('from');
$arrayHeaderTo = $parser->getAddresses('to');
$arrayHeaderFrom = $parser->getAddresses('from');

// After
$to = $parser->getTo();
$from = $parser->getFrom();
$arrayHeaderTo = $parser->getAddressesTo();
$arrayHeaderFrom = $parser->getAddressesFrom();
```

Get the subject
```php
// Before
$subject = $parser->getHeader('subject');

// After
$subject = $parser->getSubject();
```


### Get the body of the message

Return the text version

```php
// Before...
$text = $parser->getMessageBody('text');

// After
$text = $parser->getText();
```

Return the html version

```php
// Before...
$html = $parser->getMessageBody('html');

// After
$html = $parser->getHtmlNotEmbedded();
```

Return the html version with the embedded contents like images

```php
// Before...
$html = $parser->getMessageBody('htmlEmbedded');

// After
$html = $parser->getHtml();
```

### Get attachments

Return all attachments saved in the directory (include inline attachments)

```php
// Before...
$parser->saveAttachments('/path/to/save/attachments/');

// After
$parser->saveNestedAttachments('/path/to/save/attachments/', ['attachment', 'inline']);
```

Return all attachments saved in the directory (exclude inline attachments)

```php
// Before...
$parser->saveAttachments('/path/to/save/attachments/', false);

// After
$parser->saveNestedAttachments('/path/to/save/attachments/', ['attachment']);
```

Save all attachments with the strategy ATTACHMENT_DUPLICATE_SUFFIX

```php
// Before...
$parser->saveAttachments('/path/to/save/attachments/', false, Parser::ATTACHMENT_DUPLICATE_SUFFIX);

// After
$parserConfig = new ParserConfig();
$parserConfig->setFilenameStrategy(Parser::ATTACHMENT_DUPLICATE_SUFFIX);
$parser = Parser::fromPath('path/to/email.eml', $parserConfig);
$parser->saveNestedAttachments('/path/to/save/attachments/', ['attachment']);
```

Save all attachments with the strategy ATTACHMENT_RANDOM_FILENAME

```php
// Before...
$parser->saveAttachments('/path/to/save/attachments/', false, Parser::ATTACHMENT_RANDOM_FILENAME);

// After
$parserConfig = new ParserConfig();
$parserConfig->setFilenameStrategy(Parser::ATTACHMENT_RANDOM_FILENAME);
$parser = Parser::fromPath('path/to/email.eml', $parserConfig);
$parser->saveNestedAttachments('/path/to/save/attachments/', ['attachment']);
```

Save all attachments with the strategy ATTACHMENT_DUPLICATE_THROW

```php
// Before...
$parser->saveAttachments('/path/to/save/attachments/', false, Parser::ATTACHMENT_DUPLICATE_THROW);

// After
$parserConfig = new ParserConfig();
$parserConfig->setFilenameStrategy(Parser::ATTACHMENT_DUPLICATE_THROW);
$parser = Parser::fromPath('path/to/email.eml', $parserConfig);
$parser->saveNestedAttachments('/path/to/save/attachments/', ['attachment']);
```

Get all attachments (include inline attachments)

```php
// Before
$attachments = $parser->getAttachments();

// After
$attachments = $parser->getNestedAttachments(['attachment', 'inline']);
```

Get all attachments (exclude inline attachments)

```php
// Before
$attachments = $parser->getAttachments(false);

// After
$attachments = $parser->getNestedAttachments(['attachment']);
```
