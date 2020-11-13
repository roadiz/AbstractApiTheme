<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Exception;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class InvalidApiKeyException extends AccessDeniedHttpException
{

}
