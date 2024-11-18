<?php

declare(strict_types=1);

namespace PhpMimeMailParser\Helper;

class Html
{
    /**
     * Can nest this function and never double-encode.
     *
     * @param string $input *string to add entities to
     * @return string *string with HTML entities
     */
    public static function special(string $input): string
    {
        return htmlspecialchars(htmlspecialchars_decode($input, ENT_QUOTES), ENT_QUOTES);
    }

    /**
     * Make a plain text string html compatible by replacing new lines by html new line tag <br>.
     */
    public static function fromPlainText(string $plainText): string
    {
        $htmlEscapedText = self::special($plainText);

        // don't take any risks if string could not be transformed - probably because of the string encoding
        if ($htmlEscapedText === '') {
            return $plainText;
        }

        return '<html><body><p>'
            . str_replace(
                ["\r\n", "\r", "\n"],
                "<br>\n",
                $htmlEscapedText,
            )
            . '</p></body></html>';
    }
}
