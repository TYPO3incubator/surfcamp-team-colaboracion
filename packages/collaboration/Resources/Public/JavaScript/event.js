import DeferredAction from "@typo3/backend/action-button/deferred-action.js";

/**
 * Resolve SSE context:
 *   1. Prefer explicit inline settings (Edit-Screen provides them).
 *   2. Fall back to URL-based detection (Layout / List / Preview).
 *   3. Fall back to TYPO3 v14 redirect param (/typo3/main?redirect=web_layout&redirectParams=id%3D1).
 */
function resolveContext() {
  const inline = window.TYPO3?.settings?.collaboration;
  if (inline && inline.pageId && inline.module) {
    return {
      pageId: String(inline.pageId),
      module: String(inline.module),
      recordTable: inline.recordTable ? String(inline.recordTable) : null,
      recordUid: inline.recordUid ? String(inline.recordUid) : null,
    };
  }

  const url = new URL(window.location.href);
  const direct = url.searchParams.get('id');
  const redirectParams = url.searchParams.get('redirectParams');
  let pageId = direct;
  if (!pageId && redirectParams) {
    const parsed = new URLSearchParams(redirectParams);
    pageId = parsed.get('id');
  }

  const path = url.pathname;
  let module = null;
  if (path.includes('/module/web/layout')) module = 'layout';
  else if (path.includes('/module/content/records') || path.includes('/module/web/list')) module = 'records';
  else if (path.includes('/module/page-preview') || path.includes('/module/web/ViewpageView')) module = 'preview';
  else {
    const redirect = url.searchParams.get('redirect');
    if (redirect === 'web_layout') module = 'layout';
    else if (redirect === 'web_list' || redirect === 'content_records') module = 'records';
    else if (redirect === 'web_ViewpageView') module = 'preview';
  }

  if (!pageId || !module) return null;
  return { pageId, module, recordTable: null, recordUid: null };
}

function buildSseUrl(baseUrl, ctx) {
  if (!ctx) return baseUrl;
  const sep = baseUrl.includes('?') ? '&' : '?';
  const params = new URLSearchParams();
  params.set('pageId', ctx.pageId);
  params.set('module', ctx.module);
  if (ctx.recordTable) params.set('recordTable', ctx.recordTable);
  if (ctx.recordUid) params.set('recordUid', ctx.recordUid);
  return baseUrl + sep + params.toString();
}

const baseUrl = TYPO3?.settings?.ajaxUrls?.collaboration_stream;
if (!baseUrl) {
  console.error('collaboration: TYPO3.settings.ajaxUrls.collaboration_stream is not defined — SSE stream not started');
} else {

const sseUrl = buildSseUrl(baseUrl, resolveContext());
const source = new EventSource(sseUrl);

// --- Existing: lock warning notifications ---
source.addEventListener('lockedRecordEvent', (e) => {
  let data;
  try {
    data = JSON.parse(e.data);
  } catch (err) {
    console.error('Invalid lockedRecordEvent payload', err);
    return;
  }
  TYPO3.Notification.warning(data.eventData.title, data.eventData.message);
});

// --- Existing: cache clear notifications ---
source.addEventListener('clearCacheEvent', (e) => {
  let data;
  try {
    data = JSON.parse(e.data);
  } catch (err) {
    console.error('Invalid clearCacheEvent payload', err);
    return;
  }
  const deferredActionCallback = new DeferredAction(function () {
    return Promise.resolve(window.location.reload());
  });
  const actions = [{label: data.eventData.actionLabel, action: deferredActionCallback}];
  TYPO3.Notification.warning(data.eventData.title, data.eventData.message, 5, actions);
});

// --- Presence state + diff derivation ---
// Previous field focus per user, so we can emit focus-changed / blur-changed
// events when the SSE payload changes.
const previousFieldFocus = new Map(); // userId -> { recordTable, recordUid, field }

// input.js (highlight renderer) lives in the top document, but event.js runs
// inside the module iframe. CustomEvents don't cross document boundaries, so
// route field events to the top document.
function fieldEventTarget() {
  try {
    return (window.top && window.top.document) || document;
  } catch (e) {
    return document;
  }
}

function deriveFieldEvents(pageUsers) {
  const current = new Map();
  for (const user of pageUsers || []) {
    if (user.editingRecord && user.editingRecord.table && user.editingRecord.uid && user.activeField) {
      current.set(user.uid, {
        recordTable: user.editingRecord.table,
        recordUid: user.editingRecord.uid,
        field: user.activeField,
        displayName: user.displayName,
      });
    }
  }

  // Blurs: previously focused, now not (or moved to different field/record)
  for (const [userId, prev] of previousFieldFocus) {
    const now = current.get(userId);
    const changed = !now
      || now.recordTable !== prev.recordTable
      || now.recordUid !== prev.recordUid
      || now.field !== prev.field;
    if (changed) {
      fieldEventTarget().dispatchEvent(new CustomEvent('collaboration:field-blur-changed', {
        detail: {
          userId,
          recordTable: prev.recordTable,
          recordUid: prev.recordUid,
          field: prev.field,
        },
      }));
    }
  }

  // Focuses: emit every tick for any user currently focused. The listener
  // dedupes via a Map keyed by table/uid/field, so re-emitting refreshes its
  // timestamp and keeps the stale sweep from dropping the highlight while
  // the focus is unchanged.
  for (const [userId, now] of current) {
    fieldEventTarget().dispatchEvent(new CustomEvent('collaboration:field-focus-changed', {
      detail: {
        userId,
        recordTable: now.recordTable,
        recordUid: now.recordUid,
        field: now.field,
        displayName: now.displayName,
      },
    }));
  }

  previousFieldFocus.clear();
  for (const [userId, entry] of current) {
    previousFieldFocus.set(userId, entry);
  }
}

source.addEventListener('presenceUpdate', (e) => {
  let data;
  try {
    data = JSON.parse(e.data);
  } catch (err) {
    console.error('Invalid presenceUpdate payload', err);
    return;
  }
  const payload = data.eventData || data; // eliashaeussler/sse wraps in { eventData }
  document.dispatchEvent(new CustomEvent('collaboration:presence-update', {
    detail: payload,
  }));
  // Exclude current user from diff — we don't want to highlight our own field.
  const others = (payload.pageUsers || []).filter((u) => u.uid !== payload.currentUserUid);
  deriveFieldEvents(others);
});

// Close SSE connection on navigation so the server-side abort handler deletes the session row.
window.addEventListener('beforeunload', () => source.close());
window.addEventListener('pagehide', () => source.close());

source.addEventListener('ping', () => {}); // silence console spam
source.onerror = (e) => console.error('SSE error', e);

} // end: baseUrl guard
