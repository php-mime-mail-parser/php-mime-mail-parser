<?php

return [
    "subject" => "Mail de 800ko without filename",
    "from" => [
        "name" => "Name",
        "email" => "name@company.com",
        "is_group" => false,
        "header_value" => 'Name <name@company.com>',
    ],
    "to" => [
        [
        "name" => "name@company2.com",
        "email" => "name@company2.com",
        "is_group" => false,
        ],
        "header_value" => "name@company2.com",
    ],
    "textBody" => [
        "matchType" => "EXACT",
        "expectedValue" => "\n",
    ],
    "htmlBody" => [
        "matchType" => "PARTIAL",
        "expectedValue" => '<div dir="ltr"><br></div>',
    ],
    "attachments" => [
        [
            'fileName' => 'noname',
            'contentType' => 'application/octet-stream',
            'contentDisposition' => 'attachment',
            'size' => 817938,
            'fileContent' => [
                "matchType" => "PARTIAL",
                "expectedValue" => 'Suspendisse',
            ],
            'hashHeader' => '8da4b0177297b1d7f061e44d64cc766f',
            'hashEmbeddedContent' => '',
        ],
    ]
];
