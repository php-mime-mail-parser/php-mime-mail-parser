<?php

return [
    "subject" => "Testing MIME E-mail composing with cid",
    "from" => [
        "name" => "Name",
        "email" => "name@company.com",
        "is_group" => false,
        "header_value" => 'Name <name@company.com>',
    ],
    "to" => [
        [
        "name" => "Name",
        "email" => "name@company2.com",
        "is_group" => false,
        ],
        "header_value" => 'Name <name@company2.com>',
    ],
    "cc" => null,
    "textBody" => [
        "matchType" => "PARTIAL",
        "expectedValue" => "Please use an HTML capable mail program to read",
    ],
    "htmlBody" => [
        "matchType" => "PARTIAL",
        "expectedValue" => '<center><h1>Testing MIME E-mail composing with cid</h1></center>',
    ],
    "attachments" => [
        [
            'fileName' => 'logo.jpg',
            'contentType' => 'image/gif',
            'contentDisposition' => 'inline',
            'size' => 2695,
            'fileContent' => null,
            'hashHeader' => '0f65fd0831e68da313a2dcc58286d009',
            'hashEmbeddedContent' => 'IZqShSiOcB213NOfRLezbJyBjy08zKMaNHpGo9nxc49ywafxGZ',
        ],
        [
            'fileName' => 'background.jpg',
            'contentType' => 'image/gif',
            'contentDisposition' => 'inline',
            'size' => 18255,
            'fileContent' => null,
            'hashHeader' => '840bdde001a8c8f6fb49ee641a89cdd8',
            'hashEmbeddedContent' => 'QISn7+8fXB0RCQB2cyf8AcIQq2SMSQnzL',
        ],
        [
            'fileName' => 'attachment.txt',
            'contentType' => 'text/plain',
            'contentDisposition' => 'attachment',
            'size' => 2229,
            'fileContent' => [
                "matchType" => "PARTIAL",
                "expectedValue" => 'Sed pulvinar',
            ],
            'hashHeader' => '71fff85a7960460bdd3c4b8f1ee9279b',
            'hashEmbeddedContent' => '',
        ],
    ]
];
