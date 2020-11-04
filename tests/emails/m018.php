<?php

return [
    "subject" => "[Korea] Name",
    "from" => [
        "name" => "name@company.com",
        "email" => "name@company.com",
        "is_group" => false,
        "header_value" => '<name@company.com>',
        "raw" => '<name@company.com>'
    ],
    "to" => [
        [
        "name" => "name@company2.com",
        "email" => "name@company2.com",
        "is_group" => false,
        ],
        "header_value" => '"name@company2.com" <name@company2.com>',
    ],
    "textBody" => [
        "matchType" => "PARTIAL",
        "expectedValue" => "My traveling companions!",
    ],
    "htmlBody" => [
        "matchType" => "EXACT",
        "expectedValue" => '',
    ],
    "attachments" => [
        [
            'fileName' => '사진.JPG',
            'contentType' => 'image/jpeg',
            'contentDisposition' => 'attachment',
            'size' => 174,
            'fileContent' => null,
            'hashHeader' => '567f29989506f21cea8ac992d81ce4c1',
            'hashEmbeddedContent' => '',
        ],
        [
            'fileName' => 'ATT00001.txt',
            'contentType' => 'text/plain',
            'contentDisposition' => 'attachment',
            'size' => 25,
            'fileContent' => [
                "matchType" => "PARTIAL",
                "expectedValue" => 'iPhone',
            ],
            'hashHeader' => '095f96b9d5a25d051ad425356745334f',
            'hashEmbeddedContent' => '',
        ],
    ]
];
