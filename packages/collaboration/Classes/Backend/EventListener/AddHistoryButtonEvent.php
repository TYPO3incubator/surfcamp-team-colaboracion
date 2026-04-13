<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\Backend\EventListener;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonSize;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Imaging\IconFactory;

#[AsEventListener(
    identifier: 'my-extension/backend/modify-button-bar',
)]
final readonly class AddHistoryButtonEvent
{
    public function __construct(
        protected readonly ComponentFactory $componentFactory,
        protected readonly IconFactory $iconFactory,
        protected readonly UriBuilder $uriBuilder,
    ) {}

    public function __invoke(ModifyButtonBarEvent $event): void
    {
        if (str_starts_with($event->getRequest()->getAttribute('route')->getPath(), '/module/web/layout')) {
            $buttons = $event->getButtons();

            $request = $event->getRequest();
//            $urlParameters = [
//                'element' => $schema->getName() . ':' . $uid,
//                'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
//            ];
//            $actions['recordHistoryUrl'] = (string)$this->uriBuilder->buildUriFromRoute('record_history', $urlParameters);

            $showHistoryAnchor = $this->componentFactory->createGenericButton()
                ->setHref('/test')
                ->setClasses('btn-borderless')
                ->setLabel('History')
                ->setSize(ButtonSize::SMALL)
                ->setShowLabelText(true);

            $buttons[ButtonBar::BUTTON_POSITION_RIGHT][-1][] = clone $showHistoryAnchor;
            $event->setButtons($buttons);
        }
    }
}
