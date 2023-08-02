<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test;

use Psl\Encoding\Base64;
use Psl\File;
use SplFileInfo;
use Symfony\Component\HttpFoundation\File\UploadedFile;

enum DummyFile: string
{
    case AudioMp3       = 'dummy_audio_tags.mp3';
    case AudioMp3NoTags = 'dummy_audio_notags.mp3';

    case ImageBmp = 'dummy_image.bmp';
    case ImageGif = 'dummy_image.gif';
    case ImageJpg = 'dummy_image.jpg';
    case ImagePng = 'dummy_image.png';
    case ImageSvg = 'dummy_image.svg';

    case Pdf = 'dummy_pdf.pdf';

    case Text = 'dummy_text.txt';

    case VideoMpeg = 'dummy_video.mpeg';

    /**
     * @return non-empty-string
     */
    public function path(): string
    {
        return __DIR__ . '/Fixtures/Resources/' . $this->value;
    }

    public function splFileInfo(): SplFileInfo
    {
        return new SplFileInfo($this->path());
    }

    public function uploadedFile(): UploadedFile
    {
        return new UploadedFile($this->path(), $this->value, test: true);
    }

    public function content(): string
    {
        return File\read($this->path());
    }

    public function base64Content(): string
    {
        return Base64\encode($this->content());
    }

    public function mimeType(): string
    {
        return match ($this) {
            self::AudioMp3, self::AudioMp3NoTags => 'audio/mpeg',
            self::ImageBmp => 'image/x-ms-bmp',
            self::ImageGif => 'image/gif',
            self::ImageJpg => 'image/jpeg',
            self::ImagePng => 'image/png',
            self::ImageSvg => 'image/svg+xml',
            self::Pdf => 'application/pdf',
            self::Text => 'text/plain',
            self::VideoMpeg => 'video/mpeg',
        };
    }
}
