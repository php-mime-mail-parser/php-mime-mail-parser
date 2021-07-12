<?php

namespace PhpMimeMailParser;

final class Person
{
    private $header;
    private $email;
    private $name;
    private $isGroup;

    public static function fromHeader($header)
    {
        $data = mailparse_rfc822_parse_addresses($header);
        $person = new static;
        $person->header = $header;
        $person->email = $data[0]['address'];
        $person->name = $data[0]['display'];
        $person->isGroup = $data[0]['is_group'];
        return $person;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getName()
    {
        return $this->name;
    }

    public function isGroup()
    {
        return $this->isGroup;
    }

    public function __toString()
    {
        return $this->header;
    }
}
