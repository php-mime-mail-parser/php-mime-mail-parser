# php-mime-mail-parser

Fully Tested Mailparse Extension Wrapper for PHP 5.4+

[![Latest Version](https://img.shields.io/packagist/v/php-mime-mail-parser/php-mime-mail-parser.svg?style=flat-square)](https://github.com/php-mime-mail-parser/php-mime-mail-parser/releases)
[![Total Downloads](https://img.shields.io/packagist/dt/php-mime-mail-parser/php-mime-mail-parser.svg?style=flat-square)](https://packagist.org/packages/php-mime-mail-parser/php-mime-mail-parser)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

## Is it reliable ?

Yes, it is.  
All the issues are reproduced, fixed and tested.

[![Build Status](https://img.shields.io/travis/php-mime-mail-parser/php-mime-mail-parser/master.svg?style=flat-square)](https://travis-ci.org/php-mime-mail-parser/php-mime-mail-parser)
[![Coverage](https://img.shields.io/coveralls/php-mime-mail-parser/php-mime-mail-parser.svg?style=flat-square)](https://coveralls.io/r/php-mime-mail-parser/php-mime-mail-parser)
[![Quality Score](https://img.shields.io/scrutinizer/g/php-mime-mail-parser/php-mime-mail-parser.svg?style=flat-square)](https://scrutinizer-ci.com/g/php-mime-mail-parser/php-mime-mail-parser)

## How to install ?

Easy way with [Composer](https://getcomposer.org/) ;)

To install this library, run the command below and you will get the latest version

	composer require php-mime-mail-parser/php-mime-mail-parser

## Requirements

The following versions of PHP are supported by this version.

* PHP 5.4
* PHP 5.5
* PHP 5.6
* PHP 7
* HHVM

Make sure you have the mailparse extension (http://php.net/manual/en/book.mailparse.php) properly installed : 

	pecl install mailparse			#PHP Version = 7
	pecl install mailparse-2.1.6		#PHP Version < 7
	
	
And imap functions with :

	apt-get install php5-imap

Take a look at [this tutorial](http://wiki.cerbweb.com/Installing_PHP_Mailparse_Ubuntu) if you find it's difficult to install mailparse on Ubuntu. 

Also note that you may need to create the 'mailparse.ini' file with 'extension=mailparse.so' inside under '/etc/php5/mods-available/' and then 'sudo php5enmod mailparse' to enable it.

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
