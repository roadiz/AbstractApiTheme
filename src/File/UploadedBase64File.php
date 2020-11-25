<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\File;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\MimeTypes;

class UploadedBase64File extends UploadedFile
{
    /**
     * @param string $base64Content
     * @param string $filenamePrefix
     */
    public function __construct(string $base64Content, string $filenamePrefix)
    {
        $filePath = tempnam(sys_get_temp_dir(), 'UploadedFile');
        $data = explode(';base64,', $base64Content);
        if (count($data) !== 2) {
            throw new \InvalidArgumentException('Input string is not a valid base64 encoded data-uri.');
        }
        $type = explode('data:', $data[0]);
        $fileContent = base64_decode($data[1]);
        file_put_contents($filePath, $fileContent);
        $mimeType = null;
        if (isset($type[1])) {
            $mimeType = $type[1];
        }
        if (null === $mimeType) {
            throw new \InvalidArgumentException('Base64 encoded data-uri does not have mime-type.');
        }

        /*
         * Some missing mime-types
         */
        if ($mimeType === 'model/gltf-binary') {
            $extension = 'glb';
        } elseif ($mimeType === 'model/gltf+json') {
            $extension = 'gltf';
        } elseif ($mimeType === 'image/vnd.radiance') {
            $extension = 'hdr';
        } else {
            $extension = MimeTypes::getDefault()->getExtensions($mimeType)[0] ?? null;
        }
        if (null === $extension) {
            throw new \InvalidArgumentException('Base64 encoded data-uri mime-type ('.$mimeType.') is unknown.');
        }

        $originalName = $filenamePrefix . '.' . $extension;

        parent::__construct($filePath, $originalName, $mimeType, null, true);
    }
}
