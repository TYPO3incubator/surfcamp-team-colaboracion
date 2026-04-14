<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\Template\Components\Buttons;

use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonInterface;

/**
 * A button that renders raw HTML without any wrapper markup.
 * Used for injecting custom web components into the DocHeader
 * where the default btn-classes would interfere with styling.
 */
class RawHtmlButton implements ButtonInterface
{
    protected string $html = '';

    public function setHtml(string $html): static
    {
        $this->html = $html;
        return $this;
    }

    public function getHtml(): string
    {
        return $this->html;
    }

    public function isValid(): bool
    {
        return trim($this->html) !== '' && $this->getType() === static::class;
    }

    public function getType(): string
    {
        return static::class;
    }

    public function render(): string
    {
        return $this->html;
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
