<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Extractor;

use Symfony\Component\HttpFoundation\Request;
use Themes\AbstractApiTheme\Entity\Application;

interface ApplicationExtractorInterface
{
    /**
     * Tells if the given requests carries an API key.
     *
     * @param Request $request
     *
     * @return bool
     */
    public function hasApiKey(Request $request): bool;

    /**
     * Extract the Application from the given request
     *
     * @param Request $request
     *
     * @return Application|null
     */
    public function extractApplication(Request $request): ?Application;
}
