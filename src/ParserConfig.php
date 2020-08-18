<?php namespace PhpMimeMailParser;

use PhpMimeMailParser\Contracts\AttachmentInterface;
use PhpMimeMailParser\Contracts\CharsetManager;
use PhpMimeMailParser\Contracts\ContentTransferEncodingManager;
use PhpMimeMailParser\Contracts\MimeHeaderEncodingManager;

/**
 * Parser config
 *
 * @see \Tests\PhpMimeMailParser\ParserTest
 */
final class ParserConfig
{
    /**
     * @var \PhpMimeMailParser\Contracts\CharsetManager
     */
    private $charsetManager;
    /**
     * @var \PhpMimeMailParser\Contracts\ContentTransferEncodingManager
     */
    private $contentTransferEncodingManager;
    /**
     * @var \PhpMimeMailParser\Contracts\MimeHeaderEncodingManager
     */
    private $mimeHeaderEncodingManager;
    /**
     * @var \PhpMimeMailParser\Contracts\AttachmentInterface
     */
    private $attachmentInterface;
    /**
     * @var string
     */
    private $filenameStrategy;
    /**
     * Stack of middleware registered to process data
     *
     * @var MiddlewareStack
     */
    public $middlewareStack;

    public function __construct()
    {
        $this->setCharsetManager(new Charset());
        $this->setContentTransferEncodingManager(new ContentTransferDecoder());
        $this->setMimeHeaderEncodingManager(new MimeHeaderDecoder(
            $this->getCharsetManager(),
            $this->getContentTransferEncodingManager()
        ));
        $this->setAttachmentInterface(new Attachment);
        $this->setFilenameStrategy(Parser::ATTACHMENT_DUPLICATE_SUFFIX);

        $this->middlewareStack = new MiddlewareStack();
    }

    public function setCharsetManager(CharsetManager $charsetManager): void
    {
        $this->charsetManager = $charsetManager;
    }

    public function setContentTransferEncodingManager(ContentTransferEncodingManager $contentTransferEncodingManager): void
    {
        $this->contentTransferEncodingManager = $contentTransferEncodingManager;
    }

    public function setMimeHeaderEncodingManager(MimeHeaderEncodingManager $mimeHeaderEncodingManager): void
    {
        $this->mimeHeaderEncodingManager = $mimeHeaderEncodingManager;
    }

    public function setAttachmentInterface(AttachmentInterface $attachmentInterface): void
    {
        $this->attachmentInterface = $attachmentInterface;
    }

    public function setFilenameStrategy(string $filenameStrategy): void
    {
        $this->filenameStrategy = $filenameStrategy;
    }

    public function getCharsetManager(): CharsetManager
    {
        return $this->charsetManager;
    }

    public function getContentTransferEncodingManager(): ContentTransferEncodingManager
    {
        return $this->contentTransferEncodingManager;
    }

    public function getMimeHeaderEncodingManager(): MimeHeaderEncodingManager
    {
        return $this->mimeHeaderEncodingManager;
    }

    public function getAttachmentInterface(): AttachmentInterface
    {
        return $this->attachmentInterface;
    }

    public function getFilenameStrategy(): string
    {
        return $this->filenameStrategy;
    }

    /**
     * Add a middleware to the parser MiddlewareStack
     * Each middleware is invoked when:
     *   a MimePart is retrieved by mailparse_msg_get_part_data() during $this->parse()
     * The middleware will receive MimePart $part and the next MiddlewareStack $next
     *
     * Eg:
     *
     * $Parser->addMiddleware(function(MimePart $part, MiddlewareStack $next) {
     *      // do something with the $part
     *      return $next($part);
     * });
     *
     * @param callable|Middleware $middleware Plain Function or Middleware Instance to execute
     * @return void
     */
    public function addMiddleware($middleware): void
    {
        if (!$middleware instanceof Middleware) {
            $middleware = new Middleware($middleware);
        }
        $this->middlewareStack = $this->middlewareStack->add($middleware);
    }
}
