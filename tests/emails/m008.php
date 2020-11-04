<?php

return [
    "subject" => "Testing MIME E-mail composing with cid",
    "from" => [
        "name" => "Name",
        "email" => "name@company.com",
        "is_group" => false,
        "header_value" => "Name <name@company.com>",
    ],
    "to" => [
        [
        "name" => "Name",
        "email" => "name@company2.com",
        "is_group" => false,
        ],
        "header_value" => "Name <name@company2.com>",
    ],
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
            'hashHeader' => '102aa12e16635bf2b0b39ef6a91aa95c',
            'hashEmbeddedContent' => 'jRDTrOq6K1YUb35G6buFv5q919oxtqolxW',
        ],
        [
            'fileName' => 'background.jpg',
            'contentType' => 'image/gif',
            'contentDisposition' => 'inline',
            'size' => 18255,
            'fileContent' => null,
            'hashHeader' => '798f976a5834019d3f2dd087be5d5796',
            'hashEmbeddedContent' => 'EFBenQRYjVhYUalipKqETqIF6hClamCGGilLAAKY45a0x',
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
