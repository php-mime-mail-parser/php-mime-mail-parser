<?php

return [
    "subject" => "50032266 CAR 11_MNPA00A01_9PTX_H00 ATT N° 1467829. pdf",
    "from" => [
        "name" => "NAME Firstname",
        "email" => "firstname.name@groupe-company.com",
        "is_group" => false,
        "header_value" => 'NAME Firstname <firstname.name@groupe-company.com>',
    ],
    "to" => [
        [
        "name" => "paul.dupont@company.com",
        "email" => "paul.dupont@company.com",
        "is_group" => false,
        ],
        "header_value" => '"paul.dupont@company.com" <paul.dupont@company.com>',
    ],
    "textBody" => [
        "matchType" => "PARTIAL",
        "expectedValue" => "Superviseur de voitures",
    ],
    "htmlBody" => [
        "matchType" => "EXACT",
        "expectedValue" => '',
    ],
    "attachments" => [
        [
            'fileName' => '50032266 CAR 11_MNPA00A01_9PTX_H00 ATT N° 1467829.pdf',
            'contentType' => 'application/pdf',
            'contentDisposition' => 'attachment',
            'size' => 10,
            'fileContent' => null,
            'hashHeader' => 'ffe2cb0f5df4e2cfffd3931b6566f3cb',
            'hashEmbeddedContent' => '',
        ],
    ]
];
