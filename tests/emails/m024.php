<?php

return [
    "subject" => "Persil, abeilles ...",
    "from" => [
        "name" => "John DOE",
        "email" => "blablafakeemail@provider.fr",
        "is_group" => false,
        "header_value" => 'John DOE <blablafakeemail@provider.fr>',
    ],
    "to" => [
        [
        "name" => "list-name",
        "email" => "list-name@list-domain.org",
        "is_group" => false,
        ],
        "header_value" => 'list-name <list-name@list-domain.org>',
    ],
    "cc" => null,
    "textBody" => [
        "matchType" => "EXACT",
        "expectedValue" => "",
    ],
    "htmlBody" => [
        "matchType" => "EXACT",
        "expectedValue" => '',
    ],
    "attachments" => [
        [
            'fileName' => 'Biodiversité de semaine en semaine.doc',
            'contentType' => 'application/msword',
            'contentDisposition' => 'attachment',
            'size' => 27648,
            'fileContent' => null,
            'hashHeader' => '57e8a3cf9cc29d5cde7599299a853560',
            'hashEmbeddedContent' => '',
        ],
    ]
];
