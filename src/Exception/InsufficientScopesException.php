<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Exception;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class InsufficientScopesException extends AccessDeniedHttpException
{
    /**
     * @var TokenInterface
     */
    private $token;

    /**
     * @param TokenInterface $token
     * @param \Throwable|null $previous The previous exception
     * @param int $code The internal exception code
     * @param array $headers
     */
    public function __construct(TokenInterface $token, \Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct('The token has insufficient scopes.', $previous, $code, $headers);
        $this->token = $token;
    }

    /**
     * @return TokenInterface
     */
    public function getToken(): TokenInterface
    {
        return $this->token;
    }
}
