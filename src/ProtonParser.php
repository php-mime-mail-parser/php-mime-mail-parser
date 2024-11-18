<?php

namespace PhpMimeMailParser;

use PhpMimeMailParser\Enum\BodyType;
use PhpMimeMailParser\Helper\Html;

/**
 * Facade for MimeMailParser.
 *
 * @psalm-type ParsedText = array{body: string, encoding: string}|array{}
 * @psalm-type ParsedBodyPart = array{
 *        text: array<ParsedText>,
 *        html: array<ParsedText>,
 *        type: 'text'|'html'|'alternative'|'',
 *        isInlineImage: bool
 * }
 */
final class ProtonParser extends Parser
{
    private const MIME_TEXT_TYPES = [
        'text' => ['text/plain', 'text', 'plain/text'], // add misspellings as a hack for some stupid emails
        'html' => ['text/html'],
    ];
    private const IS_INLINE_IMAGE = 'isInlineImage';

    public const CONTENT_TYPES = [
        'application/octet-stream' => null,
        'application/x-msdownload' => null,
        'application/zip' => 'zip',
        'text/calendar' => 'ics',
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/gif' => 'gif',
        'image/png' => 'png',
        'image/bmp' => 'bmp',
        'video/3gpp' => '3gp',
        'audio/3gpp' => '3gp',
        'video/3gpp2' => '3g2',
        'audio/3gpp2' => '3g2',
        'video/mp4' => 'mp4',
        'audio/mp4' => 'mp4',
        'audio/wav' => 'wav',
        'audio/mpeg' => 'mp3',
        'application/msword' => 'doc',
        'application/gzip' => 'gz',
        'application/gunzip' => 'gz',
        'application/x-gzip' => 'gz',
        'application/x-gunzip' => 'gz',
        'message/rfc822' => 'eml',
        'application/pgp-keys' => 'asc',
    ];

    public function rewindStream(): void
    {
        rewind($this->stream);
    }

    public function hasStream(): bool
    {
        return is_resource($this->stream);
    }

    public function hasText(): bool
    {
        return is_string($this->data) && strlen($this->data) > 0;
    }

    public function getContents(): string|false
    {
        if ($this->hasStream()) {
            return stream_get_contents($this->stream);
        }

        if ($this->hasText()) {
            return $this->data;
        }

        return false;
    }

    public function getParts(): array
    {
        return $this->parts;
    }

    /**
     * @return array[]|bool False if nothing
     */
    public function getHtmlMessageBody()
    {
        return $this->getMessageBody('html');
    }

    /**
     * @return array[]|bool False if nothing
     */
    public function getTextMessageBody()
    {
        return $this->getMessageBody('text');
    }

    // --------------------------------------
    // Back-ported from our custom fork:
    // --------------------------------------

    /**
     * Hack for duplicate headers which should not be duplicated.
     *
     * @param mixed $header
     * @return mixed
     */
    private function pickOne($header)
    {
        if (is_array($header)) {
            return $header[0];
        }

        return $header;
    }

    /**
     * Returns the report sections if top-level content type is multipart/report.
     *
     * @internal Back-ported from custom mime parser
     */
    public function getReport(array $nestedParts): array
    {
        $bodies = [];
        $otherParts = [];

        foreach ($nestedParts as $part) {
            $contentType = $part['content-type'];
            $bodyType = $this->getBodyTypeIfText($contentType);

            if (!empty($bodyType)) {
                $bodies[] = $this->calculateTextBody($part);
            } elseif (str_starts_with($contentType, 'multipart')) {
                $bodyParts = $this->calculateBodyFromNestedParts($part);
                $bodies = array_merge($bodies, $bodyParts);
            } else {
                $headers = $this->getPartHeaders($part);

                $decodedBody = $this->decodeContentTransfer(
                    $this->getPartBody($part),
                    array_key_exists('content-transfer-encoding', $headers)
                        ? $this->pickOne($headers['content-transfer-encoding']) : '',
                );
                if ($decodedBody === false) {
                    continue;
                }

                $prefix = "\n----------------------------------------------\n";
                $prefix .= ($contentType . "\n");
                $prefix .= "----------------------------------------------\n";

                $otherParts[] = ['body' => $prefix . $decodedBody, 'encoding' => $part['content-charset'] ?? ''];
            }
        }

        $bodies = $this->mergeBodies($bodies);
        $bodies['type'] = 'report';

        if (empty($otherParts)) {
            return $bodies;
        }

        // add other parts to html if exists or text otherwise
        if ($bodies['html']) {
            $otherParts = $this->convertTextBodiesToHtml($otherParts);
            $bodies['html'] = array_merge($bodies['html'], $otherParts);
        } elseif ($bodies['text']) {
            $bodies['text'] = array_merge($bodies['text'], $otherParts);
        } else {
            $bodies['text'] = $otherParts;
        }

        return $bodies;
    }

    public function extractBody(): array
    {
        $parts = $this->getParts();
        $firstPartKey = array_key_first($parts);
        $nestedParts = $this->organizePartsInTree($parts, (string) $firstPartKey);

        if (
            !empty($nestedParts)
            && 'multipart/report' === $nestedParts['content-type']
            && !empty($nestedParts['children'])
        ) {
            return $this->getReport($nestedParts['children']);
        }

        $childrenBodies = $this->calculateBodyFromNestedParts($nestedParts);

        return $this->mergeBodies($childrenBodies);
    }

    private function mergeBodies(array $bodies): array
    {
        $body = [];
        foreach (['text', 'html'] as $type) {
            $body[$type] = [];
            foreach ($bodies as $childBody) {
                $body[$type] = array_merge($body[$type], $childBody[$type]);
            }
            $body[$type] = array_filter($body[$type]);
            if (empty($body[$type])) {
                $body[$type] = false;
            }
        }

        return $body;
    }

    /**
     * Transform an array with the part tree structure into a recursive one. It's a recursive method.
     *
     * @param array  $parts          is the result of the multipart parser. It's an array with the tree structure as key
     *                               and the content as value. For instance, for a single multipart/mixed it will have
     *                               something like:
     *                               [ '1' => partContent,
     *                               '1.1' => firstMixedContent,
     *                               '1.2' => secondMixedContent,
     *                               '1.2.1' => nextesMixedContent
     *                               ....
     *                               ]
     * @param string $currentParentID is the parent part id for this level. Initial call will always have '1'
     * @return array contentValues will have a new 'children' attribute with the part children ordered
     */
    public function organizePartsInTree(array $parts, string $currentParentID): array
    {
        $parentPart = $parts[$currentParentID];
        $children = [];

        foreach ($parts as $partID => $partContent) {
            $partID = (string) $partID;
            $pos = strrpos($partID, '.');
            $parentID = substr($partID, 0, $pos === false ? 0 : $pos);

            if ($currentParentID === $parentID) {
                $children[] = $this->organizePartsInTree($parts, $partID);
            }
        }

        $parentPart['children'] = $children;
        $parentPart['content-type'] = $this->getPartContentType($parentPart);

        return $parentPart;
    }

    /**
     * Calculate the html and text body parts and concatenate them together from the different multipart parts taking
     * into account multipart/mixed, multipart/relative and multipart/alternative.
     *
     * @param array $part is the recursively nested part contents
     * @return array<ParsedBodyPart>
     */
    private function calculateBodyFromNestedParts(array $part): array
    {
        $contentType = $this->getPartContentType($part);

        if (str_starts_with($contentType, 'multipart/')) {
            $childrenBodies = array_filter(array_map(
                fn ($child) => $this->calculateBodyFromNestedParts($child),
                $part['children'],
            ));

            if ('multipart/alternative' === $contentType) {
                $body = ['text' => [], 'html' => [], 'type' => 'alternative', self::IS_INLINE_IMAGE => false];
                //For multipart, flatten out children body list of list into a single text and html
                foreach ($childrenBodies as $childBodies) {
                    foreach ($childBodies as $childBody) {
                        $body['text'] = array_merge($body['text'], $childBody['text']);
                        $body['html'] = array_merge($body['html'], $childBody['html']);
                    }
                }

                return [$body];
            }

            $isMultipartTextWithInlineImage = $this->isMultipartTextWithInlineImage($childrenBodies);
            $bodyType = $this->getMultipartBodyType($childrenBodies, $isMultipartTextWithInlineImage);

            $bodies = [];
            foreach ($childrenBodies as $childBodies) {
                foreach ($childBodies as $childBody) {
                    $bodies[] = $this->buildMultipartBodyForChild(
                        $childBody,
                        $bodyType,
                        $isMultipartTextWithInlineImage,
                    );
                }
            }

            return $bodies;
        }

        return [$this->calculateTextBody($part)];
    }

    /**
     * @psalm-return ParsedBodyPart
     */
    private function calculateTextBody(array $part): array
    {
        $body = ['text' => [], 'html' => [], 'type' => '', self::IS_INLINE_IMAGE => false];

        foreach (self::MIME_TEXT_TYPES as $type => $mimeTypes) {
            if (in_array($part['content-type'], $mimeTypes, true)) {
                $body[$type][] = $this->extractPartBody($part);
                $body['type'] = $type;

                return $body;
            }
        }
        if ($this->isInlineImage($part)) {
            $body['html'][] = $this->getHtmlBodyForInlineImage($part);
            $body['type'] = 'html';
            $body[self::IS_INLINE_IMAGE] = true;

            return $body;
        }

        return $body;
    }

    /**
     * @param array $part
     * @return ParsedText
     */
    private function getHtmlBodyForInlineImage(array $part): array
    {
        // create an html containing just a cid
        $contentID = $this->generateContentIdForPart($part);
        $body = "<html><body><img src=\"cid:$contentID\"></body></html>";

        return [
            'body' => $body,
            'encoding' => 'utf8',
        ];
    }

    /**
     * @param array $part
     * @return ParsedText
     */
    private function extractPartBody(array $part): array
    {
        $bodyType = $this->getBodyTypeIfText($part['content-type']);
        if (!empty($bodyType) && $this->isNotAnAttachment($part)) {
            $headers = $this->getPartHeaders($part);

            $decodedBody = $this->decodeContentTransfer(
                $this->getPartBody($part),
                array_key_exists('content-transfer-encoding', $headers)
                    ? $this->pickOne($headers['content-transfer-encoding']) : '',
            );
            if ($decodedBody === false) {
                return [
                    'body' => $bodyType === 'text'
                        ? "Error decoding message content\n" : '<div>Error decoding message content</div>',
                    'encoding' => 'utf8',
                ];
            }

            return [
                'body' => $decodedBody,
                'encoding' => $part['content-charset'] ?? '',
            ];
        }

        return [];
    }

    /**
     * Return the Headers for a MIME part
     * @param array $part
     */
    protected function getPartHeaders($part): array|false
    {
        if (isset($part['headers'])) {
            return $part['headers'];
        }
        return false;
    }

    private function isInlineImage(array $part): bool
    {
        return str_starts_with($part['content-type'] ?? '', 'image/')
            && str_starts_with($part['content-disposition'] ?? '', 'inline');
    }

    private function generateContentIdForPart(array $part): string
    {
        return hash('xxh3', $part['starting-pos'] . $part['ending-pos']);
    }

    /**
     * @param array<array<ParsedBodyPart>> $childrenBodies
     */
    private function getMultipartBodyType(array $childrenBodies, bool $isMultipartTextWithInlineImage): BodyType
    {
        if ($isMultipartTextWithInlineImage) {
            return BodyType::Html;
        }

        $bodyType = BodyType::Text;
        foreach ($childrenBodies as $childBodies) {
            foreach ($childBodies as $childBody) {
                // set body type to html only if there is html part that is not an attachment.
                // Type could be html but will be empty if it is an attachment
                if ($childBody['type'] === 'html' && !$this->isEmptyParsedText($childBody['html'])) {
                    return BodyType::Html;
                }
            }
        }

        return $bodyType;
    }

    /**
     * @param array<array<ParsedBodyPart>> $childrenBodies
     */
    private function isMultipartTextWithInlineImage(array $childrenBodies): bool
    {
        $hasHtmlInlineAttachment = false;
        foreach ($childrenBodies as $childBodies) {
            foreach ($childBodies as $childBody) {
                if ($childBody['type'] !== 'text' && !$childBody[self::IS_INLINE_IMAGE]) {
                    return false;
                }
                if ($childBody[self::IS_INLINE_IMAGE]) {
                    $hasHtmlInlineAttachment = true;
                }
            }
        }

        return $hasHtmlInlineAttachment;
    }

    /**
     * Returns the email message body in the specified format.
     *
     * @param string $type Object[optional]
     * @return array|false array Body or False if not found
     */
    public function getMessageBody($type = 'text'): array|false
    {
        if (!isset(self::MIME_TEXT_TYPES[$type])) {
            throw new \RuntimeException(
                'Invalid type specified for Parser::getMessageBody. "type" can either be text or html.',
            );
        }

        $body = [];
        foreach ($this->getParts() as $part) {
            if (
                $this->isNotAnAttachment($part, empty($body))
                && in_array($this->getPartContentType($part), self::MIME_TEXT_TYPES[$type], true)
            ) {
                $headers = $this->getPartHeaders($part);

                $decodedBody = $this->decodeContentTransfer(
                    $this->getPartBody($part),
                    array_key_exists('content-transfer-encoding', $headers)
                        ? $this->pickOne($headers['content-transfer-encoding']) : '',
                );
                if ($decodedBody === false) {
                    $body[] = [
                        'body' => $type === 'text'
                            ? "Error decoding message content\n" : '<div>Error decoding message content</div>',
                        'encoding' => 'utf8',
                    ];
                } else {
                    $body[] = [
                        'body' => $decodedBody,
                        'encoding' => $part['content-charset'] ?? '',
                    ];
                }
            }
        }

        return $body === [] ? false : $body;
    }

    /**
     * Inline but only support txt, html.
     * @param $part
     * @return bool
     */
    public function isInlineContent($part): bool
    {
        $dis = $this->getPartContentDisposition($part);

        return !$dis || $dis === 'inline' || $dis === 'infile';
    }

    /**
     * Return the Content Disposition
     * @param array $part
     */
    protected function getPartContentDisposition($part): string|false
    {
        if (isset($part['content-disposition'])) {
            return $part['content-disposition'];
        }
        return false;
    }

    private function isNotAnAttachment(array $part, bool $trueIfEmptyName = true): bool
    {
        $name = $part['content-name'] ?? $part['name'] ?? $part['disposition-filename'] ?? null;

        return $this->isInlineContent($part) || ($trueIfEmptyName && empty($name));
    }

    /**
     * Returns the attachments contents in order of appearance.
     * @return Attachment[]
     */
    public function getAttachments(): array
    {
        $attachments = [];
        $dispositions = ['attachment', 'inline'];

        $mimeEncrypted = null;
        $mimeSigned = null;
        // fix MS/Exchange garbling
        $garbledMimeEncrypt = false;

        foreach ($this->getParts() as $partID => $part) {
            $headers = $this->getPartHeaders($part);
            // Sometimes content type is quoted, remove leading/trailing quotes and whitespace
            $contentType = isset($part['content-type']) ? trim($part['content-type'], "\"'\t ") : null;

            if (
                !empty($part['disposition-filename'])
                && preg_match('/filename\*="?([^;"]+)"?/', $this->pickOne($headers['content-disposition']), $matches)
                && is_string($matches[1] ?? null)
            ) {
                // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Disposition#filename_2
                // Content-Disposition: attachment; filename*=encoded%20name.pdf
                $filename = urldecode($matches[1]);
                $this->parts[$partID]['disposition-filename'] = $part['disposition-filename'] = $filename;
            }

            // Only look for PGP/MIME at the appropriate level in the MIME tree
            $levels = explode('.', (string) $partID);
            if (count($levels) < 3 && isset($contentType)) {
                if (isset($part['content-protocol']) && !$mimeSigned && !$mimeEncrypted) {
                    if ($contentType === 'multipart/encrypted'
                        && $part['content-protocol'] === 'application/pgp-encrypted'
                    ) {
                        $mimeEncrypted = $partID;
                    }
                    if ($contentType === 'multipart/signed'
                        && $part['content-protocol'] === 'application/pgp-signature'
                    ) {
                        $mimeSigned = $partID;
                    }
                }

                if ($contentType === 'application/pgp-encrypted' && !$mimeEncrypted) {
                    $body = $this->getPartBody($part);
                    $transferEncoding = array_key_exists('content-transfer-encoding', $headers)
                        ? $this->pickOne($headers['content-transfer-encoding']) : '';
                    $decodedBody = $this->decodeContentTransfer($body, $transferEncoding);

                    if (trim($decodedBody) === 'Version: 1') {
                        // next part might be the pgp mime message
                        $garbledMimeEncrypt = explode('.', $partID);
                        $garbledMimeEncrypt[count($garbledMimeEncrypt) - 1]++;
                        $garbledMimeEncrypt = implode('.', $garbledMimeEncrypt);
                    }
                }

                if ($mimeEncrypted || $mimeSigned || $garbledMimeEncrypt) {
                    $disposition = null;

                    // PGP encrypted
                    if (
                        ($mimeEncrypted . '.2' === $partID || $garbledMimeEncrypt === $partID)
                        && $contentType === 'application/octet-stream'
                    ) {
                        $attachments = [];
                        $disposition = 'pgp-encrypted';
                        $content = $this->getAttachmentStream($part);
                        // PGP signed
                    } elseif ($mimeSigned . '.2' === $partID && $contentType === 'application/pgp-signature') {
                        $disposition = 'pgp-signature';
                        $content = $this->getAttachmentStream($part);
                    } elseif ($mimeSigned . '.1' === $partID) {
                        $disposition = 'pgp-signed';
                        $partContent = $this->getPartRaw($part);

                        // Bug in mailparse_msg_get_structure: when encountering a composite part (e.g. multipart/mixed),
                        // the last newline is not removed in `ending-pos-body`, but the `line-count` is correct.
                        // When detecting an extra newline at the end of the string, we proceed with truncating it.
                        if (
                            (substr_count($partContent, "\n") - $part['line-count'] === 1)
                            && str_ends_with($partContent, "\n")
                        ) {
                            $cutLength = str_ends_with($partContent, "\r\n") ? 2 : 1;
                            $partContent = substr($partContent, 0, -$cutLength);
                        }

                        $content = $this->getStream($partContent);
                    }

                    if ($disposition) {
                        $attachments[] = new Attachment(
                            $disposition,
                            $this->getPartContentType($part),
                            $content,
                            $disposition,
                            $headers,
                        );

                        if ($disposition === 'pgp-encrypted') {
                            // We are done, no need to parse further
                            break;
                        }

                        continue;
                    }
                }
            }

            // Regular attachments
            /** @var string|false $disposition */
            $disposition = $this->getPartContentDisposition($part);
            if ($disposition !== false && !in_array($disposition, $dispositions, true)) {
                // fallback in case content-disposition is set but not recognized
                $disposition = 'attachment';
                $headers['content-disposition'] = $disposition;
            }
            assert(is_string($contentType) || is_null($contentType));
            if (
                (
                    $disposition !== false
                    && (isset($part['content-name']) || isset($part['name']) || isset($part['disposition-filename']))
                )
                || (isset($contentType) && $this->isAcceptedContentType($contentType))
            ) {
                $defaultName = 'default';
                if (isset($contentType, self::CONTENT_TYPES[$contentType])) {
                    $defaultName .= ('.' . self::CONTENT_TYPES[$contentType]);
                }
                if (isset($contentType) && 'text/calendar' === $contentType) {
                    $defaultName = 'calendar.ics';
                }
                if (isset($contentType) && 'message/rfc822' === $contentType) {
                    $defaultName = "email-$partID.eml";
                }
                if (isset($contentType) && 'application/pgp-keys' === $contentType) {
                    $defaultName = "openpgp-key-$partID.asc";
                }

                // Attachment naming priority list
                $name = (isset($part['disposition-filename'])) ? $part['disposition-filename'] : '';
                $name = (strlen($name) === 0 && isset($part['content-name'])) ? $part['content-name'] : $name;
                $name = (strlen($name) === 0 && isset($part['name'])) ? $part['name'] : $name;
                $name = (strlen($name) === 0 && isset($part['content-location'])) ? $part['content-location'] : $name;
                // Content name
                if (strlen($name) === 0 && isset($part['content-location'])) {
                    $path = parse_url($part['content-location'], PHP_URL_PATH);
                    setlocale(LC_ALL, 'en_US.utf8');
                    $name = basename($path);
                }
                // Content ID
                $name = (strlen($name) === 0 && isset($part['content-id']))
                    ? str_replace('>', '', str_replace('<', '', $part['content-id'])) : $name;
                // Default
                $name = strlen(str_replace('/', '', $name)) === 0 ? $defaultName : $name;

                // Guess at missing disposition
                if (!$disposition) {
                    if (isset($headers['content-id']) || isset($headers['content-location'])) {
                        $disposition = 'inline';
                    } else {
                        $disposition = 'attachment';
                    }
                    $headers['content-disposition'] = $disposition;
                }
                if ($this->isInlineImage($part)
                    && !isset($headers['content-id']) && !isset($headers['content-location'])
                ) {
                    // no identifier for inline image, add one
                    $headers['content-id'] = $this->generateContentIdForPart($part);
                }

                $attachments[] = new Attachment(
                    $name,
                    $this->getPartContentType($part),
                    $this->getAttachmentStream($part),
                    $disposition,
                    $headers,
                );
            }
        }

        return $attachments;
    }

    private function isAcceptedContentType(string $contentType): bool
    {
        if (array_key_exists($contentType, self::CONTENT_TYPES)) {
            return true;
        }

        $toleratedContentTypes = [
            'application/vnd.ms-', // Microsoft Office
            'application/vnd.openxmlformats-officedocument', // MS Office
            'message/rfc822', // RFC822 eml as attachments
            'application/gtar', // FortiGate sends such type
        ];
        foreach ($toleratedContentTypes as $toleratedContentType) {
            if (stripos($contentType, $toleratedContentType) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieve the raw Header of a MIME part.
     *
     * @param $part Object
     * @return string
     * @internal Added the `&` to be compatible with the original lib
     */
    protected function getPartHeaderRaw(&$part)
    {
        return $this->getData($part['starting-pos'], $part['starting-pos-body']);
    }

    /**
     * Retrieve the Body of a MIME part.
     *
     * phpcs:disable SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
     * @param $part Object
     * @return string
     * @internal Added the `&` to be compatible with the original lib
     */
    protected function getPartBody(&$part)
    {
        return $this->getData($part['starting-pos-body'], $part['ending-pos-body']);
    }
    /**
     * phpcs:disable SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
     */
    protected function getPartRaw($part)
    {
        return $this->getData($part['starting-pos'], $part['ending-pos-body']);
    }

    protected function getData($start, $end): string|false
    {
        if ($end <= $start) {
            return '';
        }

        if ($this->stream) {
            fseek($this->stream, $start, SEEK_SET);

            return fread($this->stream, $end - $start);
        } elseif ($this->data) {
            return substr($this->data, $start, $end - $start);
        }

        throw new \RuntimeException(
            'Parser::setPath() or Parser::setText() must be called before retrieving email parts.',
        );
    }

    protected function getStream($data)
    {
        $tempFp = tmpfile();

        if ($tempFp) {
            fwrite($tempFp, $data, strlen($data));
            fseek($tempFp, 0, SEEK_SET);
        } else {
            throw new \RuntimeException(
                'Could not create temporary files for attachments. Your tmp directory may be unwritable by PHP.',
            );
        }

        return $tempFp;
    }

    /**
     * Read the attachment Body and save temporary file resource.
     *
     * @param array $part
     * @return resource|false Mime Body Part
     * @internal Added the `&` to be compatible with the original lib
     */
    protected function getAttachmentStream(&$part)
    {
        $tempFp = tmpfile();

        $encoding = array_key_exists('content-transfer-encoding', $part['headers'])
            ? $this->pickOne($part['headers']['content-transfer-encoding']) : '';

        if ($tempFp) {
            if ($this->stream) {
                $start = $part['starting-pos-body'];
                $end = $part['ending-pos-body'];
                fseek($this->stream, $start, SEEK_SET);
                $len = $end - $start;
                $written = 0;
                $write = 2028;
                while ($written < $len) {
                    if (($written + $write < $len)) {
                        $write = $len - $written;
                    } elseif ($len < $write) {
                        $write = $len;
                    }
                    $attachment = fread($this->stream, $write);
                    fwrite($tempFp, $this->decodeContentTransfer($attachment, $encoding));
                    $written += $write;
                }
            } elseif ($this->data) {
                $attachment = $this->decodeContentTransfer($this->getPartBody($part), $encoding);
                fwrite($tempFp, $attachment, strlen($attachment));
            }
            fseek($tempFp, 0, SEEK_SET);
        } else {
            throw new \RuntimeException(
                'Could not create temporary files for attachments. Your tmp directory may be unwritable by PHP.',
            );
        }

        return $tempFp;
    }

    /**
     * @param ParsedBodyPart $childBody
     * @psalm-return ParsedBodyPart
     */
    private function buildMultipartBodyForChild(
        array $childBody,
        BodyType $multipartBodyType,
        bool $isMultipartTextWithInlineImage,
    ): array {
        if ('alternative' === $childBody['type']) {
            return $childBody;
        }

        $html = array_filter($childBody['html']);
        if (empty($childBody['html']) && $multipartBodyType === BodyType::Html) {
            $html = array_filter($this->convertTextBodiesToHtml($childBody['text']));
        }
        if ($childBody[self::IS_INLINE_IMAGE] && !$isMultipartTextWithInlineImage) {
            // only add inline images in body if whole multipart body is text-with-inline
            $html = [];
        }

        return [
            'type' => $multipartBodyType->value,
            'text' => $childBody['text'],
            'html' => $html,
            self::IS_INLINE_IMAGE => $childBody[self::IS_INLINE_IMAGE],
        ];
    }

    private function getBodyTypeIfText(string $contentType): ?string
    {
        foreach (self::MIME_TEXT_TYPES as $type => $mimeTypes) {
            if (in_array($contentType, $mimeTypes, true)) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Return the ContentType of the MIME part. Overrides the parent function to always return string.
     *
     * phpcs:disable SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
     * @return string
     */
    protected function getPartContentType($part)
    {
        return $part['content-type'] ?? '';
    }

    /**
     * @return array<ParsedText>
     */
    private function convertTextBodiesToHtml(array $textBodies): array
    {
        return array_map(static function ($part) {
            isset($part['body']) && $part['body'] = Html::fromPlainText($part['body']);

            return $part;
        }, $textBodies);
    }

    /**
     * @param array<ParsedText> $htmlBody
     * @return bool
     */
    private function isEmptyParsedText(array $htmlBody): bool
    {
        return array_merge(...array_values($htmlBody)) === [];
    }
}
