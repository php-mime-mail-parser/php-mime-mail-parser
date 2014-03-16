php-mime-mail-parser
====================

This project strives to create a fast and efficient PHP Mime Mail Parser Class using PHP's MailParse Extension.

## Is it reliable ?

Yes, it is. 
All the issues are reproduced, fixed and tested.

## How to install ?

### 0/ Create a folder
	mkdir myproject

### 1/ Install Composer in the folder
Go in the folder myproject

	curl -s http://getcomposer.org/installer | php
You will have myproject/composer.phar

### 2/ Install PhpMimeMailParser with Composer
Create new file myproject/composer.json

	vi composer.json

And write in

	{
	    "require": {
	        "exorus/php-mime-mail-parser": "dev-master"
	    }
	}
Save it :)

and launch the install

	php composer.phar install

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

