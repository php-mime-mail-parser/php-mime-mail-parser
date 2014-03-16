<?php

namespace eXorus\PhpMimeMailParser;
use eXorus\PhpMimeMailParser\Attachment;

/**
 * Fast Mime Mail parser Class using PHP's MailParse Extension
 * @author gabe@fijiwebdesign.com
 * @url http://www.fijiwebdesign.com/
 * @license http://creativecommons.org/licenses/by-sa/3.0/us/
 * @version r27
 *
 * This fork found on: https://github.com/eXorus/php-mime-mail-parser/
 * with contributions by eXorus 
 */

/**
 * Remove following require_once if you have PSR-0 class loader
 * @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md
 */
require_once __DIR__ . '/Attachment.php';

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
     * Stream Resources for Attachments
     */
    public $attachmentStreams;

    /**
     * Array of Content-Id
     */
    public $attachmentContentId;

    /**
     * Array of New attribut src for Content-Id
     */
    public $attachmentNewSrc;

    /**
     * Inialize some stuff
     * @return
     */
    public function __construct()
    {
        $this->attachmentStreams = array();
        $this->attachmentContentId = array();
        $this->attachmentNewSrc = array();
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
        // remove attachment resources
        foreach ($this->attachmentStreams as $stream) {
            fclose($stream);
        }
    }

    /**
     * Set the file path we use to get the email text
     * @return Object MimeMailParser Instance
     * @param $mail_path Object
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
        if (get_resource_type($stream) == 'stream') {
            $tmp_fp = tmpfile();
            if ($tmp_fp) {
                while (!feof($stream)) {
                    fwrite($tmp_fp, fread($stream, 2028));
                }
                fseek($tmp_fp, 0);
                $this->stream =& $tmp_fp;
            } else {
                throw new Exception('Could not create temporary files for attachments. Your tmp directory may be unwritable by PHP.');
                return false;
            }
            fclose($stream);
        } else {
            $this->stream = $stream;
        }

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
     * Retrieve the Email Decoded Headers
     * @return Array
     */
    public function getHeaders()
    {
        if (isset($this->parts[1])) {
            $headers = $this->getPartHeaders($this->parts[1]);
            foreach ($headers as &$header) {
                $header = $this->_decodeHeader($header);
            }
            return $headers;
        } else {
            throw new Exception('MimeMailParser::setPath() or MimeMailParser::setText() must be called before retrieving email headers.');
            return false;
        }
    }
    /**
     * Retrieve the Email Raw Headers
     * @return string
     */
    public function getHeadersRaw()
    {
        if (isset($this->parts[1])) {
            return $this->getPartHeaderRaw($this->parts[1]);
        } else {
            throw new Exception('MimeMailParser::setPath() or MimeMailParser::setText() must be called before retrieving email headers.');
            return false;
        }
    }

    /**
     * Retrieve a specific Email Header
     * @return String
     * @param $name String Header name
     */
    public function getHeader($name)
    {
        if (isset($this->parts[1])) {
            $headers = $this->getPartHeaders($this->parts[1]);
            if (isset($headers[$name])) {
                return $this->_decodeHeader($headers[$name]);
            }
        } else {
            throw new Exception('MimeMailParser::setPath() or MimeMailParser::setText() must be called before retrieving email headers.');
        }
        return false;
    }

    /**
     * Returns the email message body in the specified format
     * @return Mixed String Body or False if not found
     * @param $type Object[optional]
     */
    public function getMessageBody($type = 'text', $embeddedImg = FALSE)
    {
        $body = false;
        $mime_types = array(
            'text'=> 'text/plain',
            'html'=> 'text/html'
        );
        if (in_array($type, array_keys($mime_types))) {
            foreach ($this->parts as $part) {
                if ($this->getPartContentType($part) == $mime_types[$type] && !$this->getPartContentDisposition($part)) {
                    $headers = $this->getPartHeaders($part);
                    $encodingType = array_key_exists('content-transfer-encoding', $headers) ? $headers['content-transfer-encoding'] : '';

                    $body = $this->decodeContentTransfer($this->getPartBody($part), $encodingType);
                    $body = $this->decodeCharset($body, $this->getPartCharset($part));
                    break;
                }
            }
        } else {
            throw new Exception('Invalid type specified for MimeMailParser::getMessageBody. "type" can either be text or html.');
        }
        return ($embeddedImg === FALSE) ? $body : str_replace($this->attachmentContentId, $this->attachmentNewSrc, $body);
    }

    /**
     * get the headers for the message body part.
     * @return Array
     * @param $type Object[optional]
     */
    public function getMessageBodyHeaders($type = 'text')
    {
        $headers = false;
        $mime_types = array(
            'text'=> 'text/plain',
            'html'=> 'text/html'
        );
        if (in_array($type, array_keys($mime_types))) {
            foreach ($this->parts as $part) {
                if ($this->getPartContentType($part) == $mime_types[$type]) {
                    $headers = $this->getPartHeaders($part);
                    break;
                }
            }
        } else {
            throw new Exception('Invalid type specified for MimeMailParser::getMessageBody. "type" can either be text or html.');
        }
        return $headers;
    }

    /**
     * Returns the attachments contents in order of appearance
     * @return Array
     * @param $type Object[optional]
     */
    public function getAttachments()
    {
        $attachments = array();
        $dispositions = array('attachment','inline');
        $nonameIter = 0;

        foreach ($this->parts as $part) {
            $disposition = $this->getPartContentDisposition($part);
            $contentid = $this->getPartContentID($part);
            $filename = 'noname';

            if (isset($part['disposition-filename'])){
                $filename=$this->_decodeHeader($part['disposition-filename']);

            } else if (isset($part['content-name'])) {
                // if we have no disposition but we have a content-name, it's a valid attachment.
                // we simulate the presence of an attachment disposition with a disposition filename
                $filename=$this->_decodeHeader($part['content-name']);
                $disposition='attachment';

            }

            if (in_array($disposition, $dispositions) === TRUE && isset($filename) === TRUE) {

                if($filename == 'noname'){
                    $nonameIter++;
                    $filename = 'noname'.$nonameIter;
                }

                $attachments[] = new Attachment(
                    $filename,
                    $this->getPartContentType($part),
                    $this->getAttachmentStream($part),
                    $disposition,
                    $contentid,
                    $this->getPartHeaders($part)
                    );
            }
        }
        return $attachments;
    }

    /**
     * Save attachments in a folder
     * @return boolean
     * @param $save_dir String
     */
    public function saveAttachments($attach_dir, $url)
    {
        if (!is_dir($attach_dir)) mkdir($attach_dir);

        $attachments = $this->getAttachments();
        if (empty($attachments)) return false;

        foreach ($attachments as $attachment) {

            if ($attachment->getContentID() != ''){
                array_push($this->attachmentContentId, 'cid:'.$attachment->getContentID());
                array_push($this->attachmentNewSrc, $url.DIRECTORY_SEPARATOR.$attachment->getFilename());
            }

            if ($attachment->getFilename() != ''){
                if ($fp = fopen($attach_dir.$attachment->getFilename(), 'w')) {
                    while ($bytes = $attachment->read()) {
                        fwrite($fp, $bytes);
                    }
                    fclose($fp);
                } else {
                    return false;
                }
            }

            // write the file to the directory you want to save it in
        }
    }


    /**
     * Read the attachment Body and save temporary file resource
     * @return String Mime Body Part
     * @param $part Array
     */
    private function getAttachmentStream(&$part)
    {
        $temp_fp = tmpfile();

        $headers = $this->getPartHeaders($part);
        $encodingType = array_key_exists('content-transfer-encoding', $headers) ? $headers['content-transfer-encoding'] : '';

        if ($temp_fp) {
            if ($this->stream) {
                $start = $part['starting-pos-body'];
                $end = $part['ending-pos-body'];
                fseek($this->stream, $start, SEEK_SET);
                $len = $end-$start;
                $written = 0;
                $write = 2028;
                $body = '';
                while ($written < $len) {
                    $write = $len;
                    $part = fread($this->stream, $write);
                    fwrite($temp_fp, $this->decodeContentTransfer($part, $encodingType));
                    $written += $write;
                }
            } else if ($this->data) {
                $attachment = $this->decodeContentTransfer($this->getPartBodyFromText($part), $encodingType);
                fwrite($temp_fp, $attachment, strlen($attachment));
            }
            fseek($temp_fp, 0, SEEK_SET);
        } else {
            throw new Exception('Could not create temporary files for attachments. Your tmp directory may be unwritable by PHP.');
            return false;
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

        if ($encodingType == 'base64'){
            return base64_decode($encodedString);
        } elseif ($encodingType == 'quoted-printable') {
            return quoted_printable_decode($encodedString);
        } else {
            //8bit, 7bit, binary
            return $encodedString;
        }
    }

    /**
     * Decode the string from Charset
     * @return String the decoded string
     * @param $encodedString    The string in its original encoded state
     * @param $charset          The Charset header of the part.
     */
    private function decodeCharset($encodedString, $charset)
    {        
        return ($charset == 'us-ascii') ? $encodedString : iconv($charset, 'UTF-8//TRANSLIT', $encodedString);
    }


    /**
    * $input can be a string or an array
    * @param string,array $input
    * @return string,array
    */
    private function _decodeHeader($input)
    {
        if (is_array($input)) {

            foreach ($input as &$element) {
                $element = iconv_mime_decode($element, 0, 'UTF-8');
            }
            return $input;

        } else {
            return iconv_mime_decode($input, 0, 'UTF-8');
        }
    }


    /**
     * Return the Headers for a MIME part
     * @return Array
     * @param $part Array
     */
    private function getPartHeaders($part)
    {
        return (isset($part['headers'])) ? $part['headers'] : false;
    }

    /**
     * Return a Specific Header for a MIME part
     * @return Array
     * @param $part Array
     * @param $header String Header Name
     */
    private function getPartHeader($part, $header)
    {
        return (isset($part['headers'][$header])) ? $part['headers'][$header] : false;
    }

    /**
     * Return the ContentType of the MIME part
     * @return String
     * @param $part Array
     */
    private function getPartContentType($part)
    {
        return (isset($part['content-type'])) ? $part['content-type'] : false;
    }

    /**
     * Return the charset of the MIME part
     * @return String
     * @param $part Array
     */
    private function getPartCharset($part)
    {
        return (isset($part['charset'])) ? $part['charset'] : false;
    }

    /**
     * Return the Content Disposition
     * @return String
     * @param $part Array
     */
    private function getPartContentDisposition($part)
    {
        return (isset($part['content-disposition'])) ? $part['content-disposition'] : false;
    }

    /**
     * Return the Content ID
     * @return String
     * @param $part Array
     */
    private function getPartContentId($part)
    {
        return (isset($part['content-id'])) ? $part['content-id'] : false;
    }

    /**
     * Retrieve the raw Header of a MIME part
     * @return String
     * @param $part Object
     */
    private function getPartHeaderRaw(&$part)
    {
        $header = '';
        if ($this->stream) {
            $header = $this->getPartHeaderFromFile($part);
        } else if ($this->data) {
            $header = $this->getPartHeaderFromText($part);
        } else {
            throw new Exception('MimeMailParser::setPath() or MimeMailParser::setText() must be called before retrieving email parts.');
        }
        return $header;
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
        } else if ($this->data) {
            $body = $this->getPartBodyFromText($part);
        } else {
            throw new Exception('MimeMailParser::setPath() or MimeMailParser::setText() must be called before retrieving email parts.');
        }
        return $body;
    }

    /**
     * Retrieve the Header from a MIME part from file
     * @return String Mime Header Part
     * @param $part Array
     */
    private function getPartHeaderFromFile(&$part)
    {
        $start = $part['starting-pos'];
        $end = $part['starting-pos-body'];
        fseek($this->stream, $start, SEEK_SET);
        return fread($this->stream, $end-$start);
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
        fseek($this->stream, $start, SEEK_SET);
        return fread($this->stream, $end-$start);
    }

    /**
     * Retrieve the Header from a MIME part from text
     * @return String Mime Header Part
     * @param $part Array
     */
    private function getPartHeaderFromText(&$part)
    {
        $start = $part['starting-pos'];
        $end = $part['starting-pos-body'];
        return substr($this->data, $start, $end-$start);
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
