php-mime-mail-parser
====================

Fully Tested Mailparse Extension Wrapper for PHP 5.3+

[![Build Status](https://travis-ci.org/eXorus/php-mime-mail-parser.svg?branch=master)](https://travis-ci.org/eXorus/php-mime-mail-parser)
[![Latest Stable Version](https://poser.pugx.org/exorus/php-mime-mail-parser/v/stable.svg)](https://packagist.org/packages/exorus/php-mime-mail-parser) [![Total Downloads](https://poser.pugx.org/exorus/php-mime-mail-parser/downloads.svg)](https://packagist.org/packages/exorus/php-mime-mail-parser) [![Latest Unstable Version](https://poser.pugx.org/exorus/php-mime-mail-parser/v/unstable.svg)](https://packagist.org/packages/exorus/php-mime-mail-parser) [![License](https://poser.pugx.org/exorus/php-mime-mail-parser/license.svg)](https://packagist.org/packages/exorus/php-mime-mail-parser)

> **Maintainer:** Visit my blog [Vincent Dauce](http://vincent.dauce.fr).

## Is it reliable ?

Yes, it is.  
All the issues are reproduced, fixed and tested.

More than 52 tests and 764 assertions  
Code Coverage : 100% lines, 100% Functions and Methods, 100% Classes and Traits

## How to install ?

Easy way with [Composer](https://getcomposer.org/) ;)

	$ mkdir myproject
	$ cd myproject
	$ curl -s http://getcomposer.org/installer | php
	$ vi composer.json
	{
	    "require": {
	        "exorus/php-mime-mail-parser": "1.*"
	    }
	}
	$ php composer.phar install

## How to use it ?

```php
<?php
//We need to add the library first !
require_once __DIR__.'/vendor/autoload.php';

$path = 'path/to/mail.txt';
$Parser = new eXorus\PhpMimeMailParser\Parser();

//There are three input methods of the mime mail to be parsed
//specify a file path to the mime mail :
$Parser->setPath($path); 

// Or specify a php file resource (stream) to the mime mail :
$Parser->setStream(fopen($path, "r"));

// Or specify the raw mime mail text :
$Parser->setText(file_get_contents($path));

// We can get all the necessary data
$to = $Parser->getHeader('to');
$from = $Parser->getHeader('from');
$subject = $Parser->getHeader('subject');

$text = $Parser->getMessageBody('text');
$html = $Parser->getMessageBody('html');

// and the attachments also
$attach_dir = '/path/to/save/attachments/';
$attach_url = 'http://www.company.com/attachments/';
$Parser->saveAttachments($attach_dir, $attach_url);

// after saving attachments, you can echo the body with content-id
$html_embedded = $Parser->getMessageBody('html', TRUE);

?>
```

## Contributing ?

Feel free to contribute.  
To add issue, please provide the raw email with it.

### License

The exorus/php-mime-mail-parser is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)

