<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\Backend\Templates\Components\Buttons;

use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContextualHistoryButton implements ButtonInterface
{
    protected string $tag = 'typo3-backend-contextual-history-trigger';
    protected string $label = '';
    protected ?string $title = null;
    protected string $url = '';
    protected string $classes = '';

    public function setTag(string $tag): static
    {
        $this->tag = htmlspecialchars(trim($tag));
        return $this;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title ?? $this->label;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function getClasses(): string
    {
        return $this->classes;
    }

    public function setClasses(string $classes): static
    {
        $this->classes = $classes;
        return $this;
    }

    public function isValid(): bool
    {
        return trim($this->getLabel()) !== ''
            && $this->getType() === static::class;
    }

    public function getType(): string
    {
        return static::class;
    }

    protected function getAttributesString(): string
    {
        $attributes['class'] = rtrim($this->getClasses());
        if ($this->getUrl()) {
            $attributes['url'] = $this->getUrl();
        }
        if ($this->getLabel()) {
            $attributes['label'] = $this->getLabel();
        }
        if ($this->getTitle()) {
            $attributes['title'] = $this->getTitle();
        }

        return GeneralUtility::implodeAttributes($attributes, true);
    }

    public function render(): string
    {
        return sprintf(
            '<%1$s %2$s></%1$s>',
            $this->getTag(),
            $this->getAttributesString()
        );
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
