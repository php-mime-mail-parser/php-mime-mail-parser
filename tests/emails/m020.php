<?php

return [
    "subject" => "1",
    "from" => [
        "name" => "Finntack Newsletter",
        "email" => "newsletter@finntack.com",
        "is_group" => false,
        "header_value" => 'Finntack Newsletter <newsletter@finntack.com>',
    ],
    "to" => [
        [
        "name" => "Clement Wong",
        "email" => "clement.wong@finntack.com",
        "is_group" => false,
        ],
        "header_value" => 'Clement Wong <clement.wong@finntack.com>',
    ],
    "textBody" => [
        "matchType" => "EXACT",
        "expectedValue" => "1\r\n\r\n",
    ],
    "htmlBody" => [
        "matchType" => "PARTIAL",
        "expectedValue" => '<html>',
    ],
    "attachments" => [
        [
            'fileName' => 'noname',
            'contentType' => 'text/calendar',
            'contentDisposition' => null,
            'size' => 1432,
            'fileContent' => null,
            'hashHeader' => 'bf7bfb9b8dd11ff0c830b2388560d434',
            'hashEmbeddedContent' => '',
        ],
    ]
];
