<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\EventListener;

use TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Incubator\Collaboration\Template\Components\Buttons\RawHtmlButton;

#[AsEventListener('collaboration/add-presence-to-docheader')]
final readonly class AddPresenceToDocHeaderListener
{
    private const PAGE_MODULES = [
        'web_layout' => 'layout',
        'records' => 'records',
        'page_preview' => 'preview',
    ];

    public function __construct(
        private PageRenderer $pageRenderer,
    ) {}

    public function __invoke(ModifyButtonBarEvent $event): void
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if ($request === null) {
            return;
        }

        $moduleIdentifier = $request->getAttribute('module')?->getIdentifier() ?? '';
        $routePath = $request->getAttribute('route')?->getPath() ?? '';
        $queryParams = $request->getQueryParams();

        $context = null;

        if (isset(self::PAGE_MODULES[$moduleIdentifier])) {
            $pageId = (int)($queryParams['id'] ?? 0);
            if ($pageId > 0) {
                $context = [
                    'pageId' => $pageId,
                    'module' => self::PAGE_MODULES[$moduleIdentifier],
                    'recordTable' => null,
                    'recordUid' => null,
                ];
            }
        } elseif (
            $routePath === '/record/edit'
            || str_ends_with($routePath, '/record/edit')
            || $routePath === '/record/edit/contextual'
            || str_ends_with($routePath, '/record/edit/contextual')
        ) {
            $context = $this->resolveEditContext($queryParams);
        }

        if ($context === null) {
            return;
        }

        $this->pageRenderer->addInlineSettingArray('collaboration', $context);
        $this->pageRenderer->loadJavaScriptModule('@typo3/collaboration/event.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/collaboration/presence-avatars.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/collaboration/presence-badge.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/collaboration/presence-highlight.js');

        $button = GeneralUtility::makeInstance(RawHtmlButton::class);
        $button->setHtml('<typo3-collaboration-avatars></typo3-collaboration-avatars>');

        $buttons = $event->getButtons();
        $buttons['right'][-1][] = $button;
        $event->setButtons($buttons);
    }

    /**
     * Edit-URLs look like:
     *   /typo3/record/edit?edit[tt_content][42]=edit
     *   /typo3/record/edit?edit[tt_content][42,43]=edit
     *
     * We use the FIRST record as the "primary" presence target.
     *
     * @return array{pageId:int, module:string, recordTable:string, recordUid:int}|null
     */
    private function resolveEditContext(array $queryParams): ?array
    {
        $edit = $queryParams['edit'] ?? null;
        if (!is_array($edit)) {
            return null;
        }

        foreach ($edit as $table => $records) {
            if (!is_array($records)) {
                continue;
            }
            foreach ($records as $uidList => $_command) {
                $first = (int)explode(',', (string)$uidList)[0];
                if ($first <= 0) {
                    continue;
                }
                $pageId = $table === 'pages'
                    ? $first
                    : (int)(BackendUtility::getRecord((string)$table, $first, 'pid')['pid'] ?? 0);
                if ($pageId === 0) {
                    continue;
                }
                // Contextual edit URLs carry the host module as `?module=web_layout` etc.
                // Prefer that label so a user editing CE 38 from the layout module shows
                // up as "Layout-Modul · CE 38" instead of the generic "Edit".
                $moduleHint = (string)($queryParams['module'] ?? '');
                $module = self::PAGE_MODULES[$moduleHint] ?? 'edit';
                return [
                    'pageId' => $pageId,
                    'module' => $module,
                    'recordTable' => (string)$table,
                    'recordUid' => $first,
                ];
            }
        }

        return null;
    }
}
