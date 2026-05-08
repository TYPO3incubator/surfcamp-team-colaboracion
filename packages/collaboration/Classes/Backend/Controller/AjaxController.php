<?php

declare(strict_types=1);

namespace TYPO3Incubator\Collaboration\Backend\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3Incubator\Collaboration\Service\PresenceService;

/**
 * Focus/Blur endpoint consumed by `input.js`. Writes directly into
 * `tx_collaboration_presence` so that the SSE loop re-emits the change
 * within 500ms without touching `sys_collaboration_event`.
 */
#[AsController]
final class AjaxController
{
    public function __construct(
        private readonly PresenceService $presenceService,
    ) {}

    public function focusAction(ServerRequestInterface $request): ResponseInterface
    {
        $data = json_decode($request->getBody()->getContents(), true) ?? [];
        $userId = (int)($GLOBALS['BE_USER']->user['uid'] ?? 0);
        $sessionId = $GLOBALS['BE_USER']?->getSession()?->getIdentifier() ?? '';

        if ($userId === 0 || $sessionId === '') {
            return new JsonResponse(['status' => 'unauth'], 401);
        }

        // Sync ping: stream re-emits state, nothing to do.
        if (!empty($data['sync'])) {
            return new JsonResponse(['status' => 'ok']);
        }

        // Blur: clear record/field, keep the user on the page.
        if (!empty($data['blur'])) {
            $this->presenceService->clearFieldFocus($userId, $sessionId);
            return new JsonResponse(['status' => 'ok']);
        }

        $table = isset($data['table']) ? (string)$data['table'] : '';
        $uid = isset($data['uid']) ? (int)$data['uid'] : 0;
        $field = isset($data['field']) ? (string)$data['field'] : '';

        // Reject anything that isn't a known TCA table the user may modify — prevents
        // probing arbitrary tables and writing presence rows the user has no right to.
        if (
            $table === ''
            || !isset($GLOBALS['TCA'][$table])
            || !$GLOBALS['BE_USER']->check('tables_modify', $table)
        ) {
            return new JsonResponse(['status' => 'forbidden'], 403);
        }

        // Resolve the page the record lives on. `pages` records live on themselves.
        $pageId = $table === 'pages'
            ? $uid
            : (int)(BackendUtility::getRecord($table, $uid, 'pid')['pid'] ?? 0);

        if ($pageId === 0) {
            return new JsonResponse(['status' => 'no-page'], 422);
        }

        $this->presenceService->setFieldFocus($userId, $sessionId, $pageId, $table, $uid, $field);

        return new JsonResponse(['status' => 'ok']);
    }
}
