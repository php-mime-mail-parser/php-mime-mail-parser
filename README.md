# php-mime-mail-parser

Fully Tested Mailparse Extension Wrapper for PHP 5.4+


[![Latest Stable Version](https://poser.pugx.org/php-mime-mail-parser/php-mime-mail-parser/v/stable.svg)](https://packagist.org/packages/php-mime-mail-parser/php-mime-mail-parser) [![Total Downloads](https://poser.pugx.org/php-mime-mail-parser/php-mime-mail-parser/downloads.svg)](https://packagist.org/packages/php-mime-mail-parser/php-mime-mail-parser) [![Latest Unstable Version](https://poser.pugx.org/php-mime-mail-parser/php-mime-mail-parser/v/unstable.svg)](https://packagist.org/packages/php-mime-mail-parser/php-mime-mail-parser) [![License](https://poser.pugx.org/php-mime-mail-parser/php-mime-mail-parser/license.svg)](https://packagist.org/packages/php-mime-mail-parser/php-mime-mail-parser)

## Is it reliable ?

Yes, it is.  
All the issues are reproduced, fixed and tested.

[![Build Status](https://travis-ci.org/eXorus/php-mime-mail-parser.svg?branch=master)](https://travis-ci.org/eXorus/php-mime-mail-parser)
[![Coverage Status](https://coveralls.io/repos/eXorus/php-mime-mail-parser/badge.png?branch=master)](https://coveralls.io/r/eXorus/php-mime-mail-parser?branch=master)

## How to install ?

Easy way with [Composer](https://getcomposer.org/) ;)

To install this library, run the command below and you will get the latest version

	composer require php-mime-mail-parser/php-mime-mail-parser

## Requirements

The following versions of PHP are supported by this version.

* PHP 5.4
* PHP 5.5
* PHP 5.6
* HHVM

Make sure you have the mailparse extension (http://php.net/manual/en/book.mailparse.php) properly installed : 
	pecl install mailparse
And imap functions with :
	apt-get install php5-imap

## How to use it ?

```php
<?php
//We need to add the library first !
require_once __DIR__.'/vendor/autoload.php';

$path = 'path/to/mail.txt';
$Parser = new PhpMimeMailParser\Parser();

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
$htmlEmbedded = $Parser->getMessageBody('htmlEmbedded'); //HTML Body included data

// and the attachments also
$attach_dir = '/path/to/save/attachments/';
$Parser->saveAttachments($attach_dir);

// loop the attachments
$attachments = $Parser->getAttachments();
if (count($attachments) > 0) {
	foreach ($attachments as $attachment) {
		echo 'Filename : '.$attachment->getFilename().'<br />'; // logo.jpg
		echo 'Filesize : '.filesize($attach_dir.$attachment->getFilename()).'<br />'; // 1000
		echo 'Filetype : '.$attachment->getContentType().'<br />'; // image/jpeg
	}
}

?>
```

## Contributing ?

Feel free to contribute.  
To add issue, please provide the raw email with it.

### License

The php-mime-mail-parser/php-mime-mail-parser is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
