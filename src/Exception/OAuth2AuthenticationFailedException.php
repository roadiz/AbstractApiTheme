<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Exception;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class OAuth2AuthenticationFailedException extends UnauthorizedHttpException
{
    /**
     * @param string|null $message The internal exception message
     * @param \Throwable|null $previous The previous exception
     * @param int|null $code The internal exception code
     * @param array $headers
     */
    public function __construct(string $message = null, \Throwable $previous = null, ?int $code = 0, array $headers = [])
    {
        parent::__construct('oauth2-access-token', $message, $previous, $code, $headers);
    }
}
