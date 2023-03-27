<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test;

use Psl\File;

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

    public function content(): string
    {
        return File\read($this->path());
    }
}
