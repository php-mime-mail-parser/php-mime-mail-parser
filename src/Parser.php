<?php

namespace PhpMimeMailParser;

use PhpMimeMailParser\Attachment;
use PhpMimeMailParser\Exception;
use PhpMimeMailParser\Contracts\CharsetManager;

/**
 * Parser of php-mime-mail-parser
 *
 * Fully Tested Mailparse Extension Wrapper for PHP 5.4+
 *
 */

class Parser
{

    /**
     * PHP MimeParser Resource ID
     */
    public $resource;

    /**
     * A file pointer to email
     */
    public $stream;

    /**
     * A text of an email
     */
    public $data;

    /**
     * Parts of an email
     */
    public $parts;

    /**
     * Charset managemer object
     */
    public $charset;

    public function __construct(CharsetManager $charset = null)
    {
        if ($charset == null) {
            $charset = new Charset();
        }

        $this->charset = $charset;
    }

    /**
     * Free the held resouces
     * @return void
     */
    public function __destruct()
    {
        // clear the email file resource
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
        // clear the MailParse resource
        if (is_resource($this->resource)) {
            mailparse_msg_free($this->resource);
        }
    }

    /**
     * Set the file path we use to get the email text
     * @return Object MimeMailParser Instance
     * @param string $path File path to the MIME mail
     */
    public function setPath($path)
    {
        // should parse message incrementally from file
        $this->resource = mailparse_msg_parse_file($path);
        $this->stream = fopen($path, 'r');
        $this->parse();
        return $this;
    }

    /**
     * Set the Stream resource we use to get the email text
     * @return Object MimeMailParser Instance
     * @param $stream Resource
     */
    public function setStream($stream)
    {
        // streams have to be cached to file first
        $meta = @stream_get_meta_data($stream);
        if (!$meta || !$meta['mode'] || $meta['mode'][0] != 'r' || $meta['eof']) {
            throw new \Exception(
                'setStream() expects parameter stream to be readable stream resource.'
            );
        }

        $tmp_fp = tmpfile();
        if ($tmp_fp) {
            while (!feof($stream)) {
                fwrite($tmp_fp, fread($stream, 2028));
            }
            fseek($tmp_fp, 0);
            $this->stream =& $tmp_fp;
        } else {
            throw new \Exception(
                'Could not create temporary files for attachments. Your tmp directory may be unwritable by PHP.'
            );
        }
        fclose($stream);

        $this->resource = mailparse_msg_create();
        // parses the message incrementally (low memory usage but slower)
        while (!feof($this->stream)) {
            mailparse_msg_parse($this->resource, fread($this->stream, 2082));
        }
        $this->parse();
        return $this;
    }

    /**
     * Set the email text
     * @return Object MimeMailParser Instance
     * @param $data String
     */
    public function setText($data)
    {
        $this->resource = \mailparse_msg_create();
        // does not parse incrementally, fast memory hog might explode
        mailparse_msg_parse($this->resource, $data);
        $this->data = $data;
        $this->parse();
        return $this;
    }

    /**
     * Parse the Message into parts
     * @return void
     * @private
     */
    private function parse()
    {
        $structure = mailparse_msg_get_structure($this->resource);
        $this->parts = array();
        foreach ($structure as $part_id) {
            $part = mailparse_msg_get_part($this->resource, $part_id);
            $this->parts[$part_id] = mailparse_msg_get_part_data($part);
        }
    }

    /**
     * Retrieve a specific Email Header, without charset conversion.
     * @return String
     * @param $name String Header name
     */
    public function getRawHeader($name)
    {
        if (isset($this->parts[1])) {
            $headers = $this->getPart('headers', $this->parts[1]);
            return (isset($headers[$name])) ? $headers[$name] : false;
        } else {
            throw new \Exception(
                'setPath() or setText() or setStream() must be called before retrieving email headers.'
            );
        }
    }

    /**
     * Retrieve a specific Email Header
     * @return String
     * @param $name String Header name
     */
    public function getHeader($name)
    {
        $rawHeader = $this->getRawHeader($name);
        if ($rawHeader === false) {
            return false;
        }
        return $this->decodeHeader($rawHeader);
    }

    /**
     * Retrieve all mail headers
     * @return Array
     */
    public function getHeaders()
    {
        if (isset($this->parts[1])) {
            $headers = $this->getPart('headers', $this->parts[1]);
            foreach ($headers as $name => &$value) {
                if (is_array($value)) {
                    foreach ($value as &$v) {
                        $v = $this->decodeSingleHeader($v);
                    }
                } else {
                    $value = $this->decodeSingleHeader($value);
                }
            }
            return $headers;
        } else {
            throw new \Exception(
                'setPath() or setText() or setStream() must be called before retrieving email headers.'
            );
        }
    }

    /**
     * Returns the email message body in the specified format
     * @return Mixed String Body or False if not found
     * @param $type String text or html or htmlEmbedded
     */
    public function getMessageBody($type = 'text')
    {
        $body = false;
        $mime_types = array(
            'text'=> 'text/plain',
            'html'=> 'text/html',
            'htmlEmbedded'=> 'text/html'
        );
        if (in_array($type, array_keys($mime_types))) {
            foreach ($this->parts as $part) {
                if ($this->getPart('content-type', $part) == $mime_types[$type]
                    && $this->getPart('content-disposition', $part) != 'attachment'
                    ) {
                    $headers = $this->getPart('headers', $part);
                    $encodingType = array_key_exists('content-transfer-encoding', $headers) ?
                        $headers['content-transfer-encoding'] : '';
                    $body = $this->decodeContentTransfer($this->getPartBody($part), $encodingType);
                    $body = $this->charset->decodeCharset($body, $this->getPartCharset($part));
                    break;
                }
            }
        } else {
            throw new \Exception('Invalid type specified for getMessageBody(). "type" can either be text or html.');
        }

        if ($type == 'htmlEmbedded') {
            $attachments = $this->getAttachments();
            foreach ($attachments as $attachment) {
                if ($attachment->getContentID() != '') {
                    $body = str_replace(
                        '"cid:'.$attachment->getContentID().'"',
                        '"'.$this->getEmbeddedData($attachment->getContentID()).'"',
                        $body
                    );
                }
            }
        }
        return $body;
    }

    /**
     * Returns the embedded data structure
     * @return String
     * @param $contentId String of Content-Id
     */
    private function getEmbeddedData($contentId)
    {
        $embeddedData = 'data:';
        foreach ($this->parts as $part) {
            if ($this->getPart('content-id', $part) == $contentId) {
                $embeddedData .= $this->getPart('content-type', $part);
                $embeddedData .= ';'.$this->getPart('transfer-encoding', $part);
                $embeddedData .= ','.$this->getPartBody($part);
            }
        }
        return $embeddedData;
    }

    /**
     * Returns the attachments contents in order of appearance
     * @return Array of attachments
     */
    public function getAttachments()
    {
        $attachments = array();
        $dispositions = array('attachment','inline');
        $non_attachment_types = array('text/plain', 'text/html');
        $nonameIter = 0;

        foreach ($this->parts as $part) {
            $disposition = $this->getPart('content-disposition', $part);
            $filename = 'noname';

            if (isset($part['disposition-filename'])) {
                $filename = $this->decodeHeader($part['disposition-filename']);
            } elseif (isset($part['content-name'])) {
                // if we have no disposition but we have a content-name, it's a valid attachment.
                // we simulate the presence of an attachment disposition with a disposition filename
                $filename = $this->decodeHeader($part['content-name']);
                $disposition = 'attachment';
            } elseif (!in_array($part['content-type'], $non_attachment_types, true)
                && substr($part['content-type'], 0, 10) !== 'multipart/') {
                // if we cannot get it by getMessageBody(), we assume it is an attachment
                $disposition = 'attachment';
            }

            if (in_array($disposition, $dispositions) === true && isset($filename) === true) {
                if ($filename == 'noname') {
                    $nonameIter++;
                    $filename = 'noname'.$nonameIter;
                }

                $headersAttachments = $this->getPart('headers', $part);
                $contentidAttachments = $this->getPart('content-id', $part);

                $attachments[] = new Attachment(
                    $filename,
                    $this->getPart('content-type', $part),
                    $this->getAttachmentStream($part),
                    $disposition,
                    $contentidAttachments,
                    $headersAttachments
                );
            }
        }
        return $attachments;
    }

    /**
     * Save attachments in a folder
     * @return array Saved attachments paths
     * @param $attach_dir String of the directory
     */
    public function saveAttachments($attach_dir)
    {
        $attachments = $this->getAttachments();
        if (empty($attachments)) {
            return false;
        }

        if (!is_dir($attach_dir)) {
            mkdir($attach_dir);
        }

        $attachments_paths = array();
        foreach ($attachments as $attachment) {
            $attachment_path = $attach_dir.$attachment->getFilename();
            if ($fp = fopen($attachment_path, 'w')) {
                while ($bytes = $attachment->read()) {
                    fwrite($fp, $bytes);
                }
                fclose($fp);
                $attachments_paths[] = realpath($attachment_path);
            } else {
                throw new \Exception('Could not write attachments. Your directory may be unwritable by PHP.');
            }
        }

        return $attachments_paths;
    }

    /**
     * Read the attachment Body and save temporary file resource
     * @return String Mime Body Part
     * @param $part Array
     */
    private function getAttachmentStream(&$part)
    {
        $temp_fp = tmpfile();

        $headers = $this->getPart('headers', $part);
        $encodingType = array_key_exists('content-transfer-encoding', $headers) ?
            $headers['content-transfer-encoding'] : '';

        if ($temp_fp) {
            if ($this->stream) {
                $start = $part['starting-pos-body'];
                $end = $part['ending-pos-body'];
                fseek($this->stream, $start, SEEK_SET);
                $len = $end-$start;
                $written = 0;
                while ($written < $len) {
                    $write = $len;
                    $part = fread($this->stream, $write);
                    fwrite($temp_fp, $this->decodeContentTransfer($part, $encodingType));
                    $written += $write;
                }
            } elseif ($this->data) {
                $attachment = $this->decodeContentTransfer($this->getPartBodyFromText($part), $encodingType);
                fwrite($temp_fp, $attachment, strlen($attachment));
            }
            fseek($temp_fp, 0, SEEK_SET);
        } else {
            throw new \Exception(
                'Could not create temporary files for attachments. Your tmp directory may be unwritable by PHP.'
            );
        }
        return $temp_fp;
    }

    /**
     * Decode the string from Content-Transfer-Encoding
     * @return String the decoded string
     * @param $encodedString    The string in its original encoded state
     * @param $encodingType     The encoding type from the Content-Transfer-Encoding header of the part.
     */
    private function decodeContentTransfer($encodedString, $encodingType)
    {
        $encodingType = strtolower($encodingType);
        if ($encodingType == 'base64') {
            return base64_decode($encodedString);
        } elseif ($encodingType == 'quoted-printable') {
            return quoted_printable_decode($encodedString);
        } else {
            return $encodedString; //8bit, 7bit, binary
        }
    }

    /**
    * $input can be a string or array
    * @param string|array $input
    * @return string
    */
    private function decodeHeader($input)
    {
        //Sometimes we have 2 label From so we take only the first
        if (is_array($input)) {
            return $this->decodeSingleHeader($input[0]);
        }

        return $this->decodeSingleHeader($input);
    }

    /**
     * Decodes a single header (= string)
     * @param string
     * @return string
     */
    private function decodeSingleHeader($input)
    {
        // Remove white space between encoded-words
        $input = preg_replace('/(=\?[^?]+\?(q|b)\?[^?]*\?=)(\s)+=\?/i', '\1=?', $input);

        // For each encoded-word...
        while (preg_match('/(=\?([^?]+)\?(q|b)\?([^?]*)\?=)/i', $input, $matches)) {
            $encoded = $matches[1];
            $charset = $matches[2];
            $encoding = $matches[3];
            $text = $matches[4];


            switch (strtolower($encoding)) {
                case 'b':
                    $text = $this->decodeContentTransfer($text, 'base64');
                    break;

                case 'q':
                    $text = str_replace('_', ' ', $text);
                    preg_match_all('/=([a-f0-9]{2})/i', $text, $matches);
                    foreach ($matches[1] as $value) {
                        $text = str_replace('='.$value, chr(hexdec($value)), $text);
                    }
                    break;
            }

            $text = $this->charset->decodeCharset($text, $this->charset->getCharsetAlias($charset));
            $input = str_replace($encoded, $text, $input);
        }

        return $input;
    }

    /**
     * Return the charset of the MIME part
     * @return String|false
     * @param $part Array
     */
    private function getPartCharset($part)
    {
        if (isset($part['charset'])) {
            return $charset = $this->charset->getCharsetAlias($part['charset']);
        } else {
            return false;
        }
    }

    /**
     * Retrieve a specified MIME part
     * @return String|Array
     * @param $type String, $parts Array
     */
    private function getPart($type, $parts)
    {
        return (isset($parts[$type])) ? $parts[$type] : false;
    }

    /**
     * Retrieve the Body of a MIME part
     * @return String
     * @param $part Object
     */
    private function getPartBody(&$part)
    {
        $body = '';
        if ($this->stream) {
            $body = $this->getPartBodyFromFile($part);
        } elseif ($this->data) {
            $body = $this->getPartBodyFromText($part);
        }
        return $body;
    }

    /**
     * Retrieve the Body from a MIME part from file
     * @return String Mime Body Part
     * @param $part Array
     */
    private function getPartBodyFromFile(&$part)
    {
        $start = $part['starting-pos-body'];
        $end = $part['ending-pos-body'];
        $body = '';
        if ($end-$start > 0) {
            fseek($this->stream, $start, SEEK_SET);
            $body = fread($this->stream, $end-$start);
        }
        return $body;
    }

    /**
     * Retrieve the Body from a MIME part from text
     * @return String Mime Body Part
     * @param $part Array
     */
    private function getPartBodyFromText(&$part)
    {
        $start = $part['starting-pos-body'];
        $end = $part['ending-pos-body'];
        return substr($this->data, $start, $end-$start);
    }
}
