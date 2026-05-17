/**
 * Presence highlight + dynamic badge injection.
 *
 * Two modes:
 * 1. On page load: scans for server-rendered .t3-page-ce-editing-info elements
 *    and applies border highlights + moves them between header and body.
 * 2. On SSE presence-update: dynamically injects/removes badges and borders
 *    for records that start/stop being edited — no page reload needed.
 */

const CE_BORDER_CLASS = 't3-page-ce--collaboration-editing';
const MARKER_CLASS = 'collaboration-managed-badge';

/** @type {Map<string, HTMLElement>} key = "table:uid" → injected container */
const injectedContainers = new Map();

// --- Mode 1: Server-rendered badge handling (existing behavior) ---

function applyEditingHighlights() {
  document.querySelectorAll('.t3-page-ce-body .t3-page-ce-editing-info').forEach((info) => {
    const ceElement = info.closest('.t3-page-ce-element');
    if (!ceElement) {
      return;
    }

    const ceWrapper = ceElement.closest('.t3-page-ce');
    if (ceWrapper) {
      ceWrapper.classList.add(CE_BORDER_CLASS);
    }

    const body = ceElement.querySelector('.t3-page-ce-body');
    if (body) {
      ceElement.insertBefore(info, body);
    }
  });
}

applyEditingHighlights();

// --- Mode 2: Dynamic badge injection from SSE ---

function buildBadgeElement(table, uid) {
  // count is driven by SSE updates via Lit property; no attribute initialization
  // needed (and setting count="0" would otherwise stay stale because Lit doesn't
  // sync the property back to the attribute).
  const badge = document.createElement('typo3-collaboration-badge');
  badge.setAttribute('record-id', `${table}:${uid}`);
  return badge;
}

function injectIntoPageLayout(ceElement, table, uid) {
  const inner = ceElement.querySelector('.t3-page-ce-element');
  const body = inner?.querySelector('.t3-page-ce-body');
  if (!inner || !body) return null;

  // Don't inject if server already rendered one
  if (inner.querySelector('.t3-page-ce-editing-info')) return null;

  const wrapper = document.createElement('div');
  wrapper.className = `t3-page-ce-editing-info ${MARKER_CLASS}`;
  wrapper.appendChild(buildBadgeElement(table, uid));

  const label = document.createElement('span');
  label.className = 'collaboration-editing-label';
  label.textContent = 'This element is currently being edited.';
  wrapper.appendChild(label);

  inner.insertBefore(wrapper, body);
  return wrapper;
}

function injectIntoRecordList(tr, table, uid) {
  const control = tr.querySelector('td.col-control');
  if (!control) return null;

  // Don't inject if server already rendered one
  if (control.querySelector('typo3-collaboration-badge')) return null;

  const wrapper = document.createElement('span');
  wrapper.className = MARKER_CLASS;
  wrapper.appendChild(buildBadgeElement(table, uid));
  control.prepend(wrapper);
  return wrapper;
}

function injectBadge(table, uid) {
  // Page layout: CEs have id="element-tt_content-{uid}"
  const ce = document.getElementById(`element-${table}-${uid}`);
  if (ce) return injectIntoPageLayout(ce, table, uid);

  // Record list: rows in table with data-table and data-uid
  const tr = document.querySelector(`table[data-table="${table}"] tr[data-uid="${uid}"]`);
  if (tr) return injectIntoRecordList(tr, table, uid);

  return null;
}

function applyBorder(table, uid) {
  const ce = document.getElementById(`element-${table}-${uid}`);
  if (!ce) return;
  (ce.closest('.t3-page-ce') || ce).classList.add(CE_BORDER_CLASS);
}

function removeBorder(table, uid) {
  const ce = document.getElementById(`element-${table}-${uid}`);
  if (!ce) return;
  (ce.closest('.t3-page-ce') || ce).classList.remove(CE_BORDER_CLASS);
}

function reconcile(editingRecords) {
  const activeKeys = new Set(Object.keys(editingRecords || {}));

  // Remove badges for records no longer being edited
  for (const [key, container] of injectedContainers) {
    if (!activeKeys.has(key)) {
      container.remove();
      injectedContainers.delete(key);
      const [table, uid] = key.split(':');
      removeBorder(table, uid);
    }
  }

  // Also remove server-rendered badges that are no longer active
  document.querySelectorAll('.t3-page-ce-editing-info:not(.' + MARKER_CLASS + ')').forEach((info) => {
    const badge = info.querySelector('typo3-collaboration-badge');
    if (badge) {
      const recordId = badge.getAttribute('record-id');
      if (recordId && !activeKeys.has(recordId)) {
        const ceElement = info.closest('.t3-page-ce-element');
        if (ceElement) {
          (ceElement.closest('.t3-page-ce') || ceElement).classList.remove(CE_BORDER_CLASS);
        }
        info.remove();
      }
    }
  });

  // Inject badges for newly edited records
  for (const key of activeKeys) {
    if (injectedContainers.has(key)) continue;
    // Also skip if server already rendered a badge for this record
    if (document.querySelector(`typo3-collaboration-badge[record-id="${key}"]`)) continue;

    const [table, uid] = key.split(':');
    const container = injectBadge(table, uid);
    if (container) {
      injectedContainers.set(key, container);
      applyBorder(table, uid);
    }
  }

  // Re-apply borders for all active records (safety net for DOM churn)
  for (const key of activeKeys) {
    const [table, uid] = key.split(':');
    applyBorder(table, uid);
  }
}

document.addEventListener('collaboration:presence-update', (e) => {
  reconcile(e.detail?.editingRecords || {});
});

// Detect DOM churn (drag-drop re-renders) so the next presence-update re-injects
const observer = new MutationObserver(() => {
  for (const [key, container] of injectedContainers) {
    if (!container.isConnected) {
      injectedContainers.delete(key);
    }
  }
  // Re-apply server-rendered highlights after DOM changes
  applyEditingHighlights();
});
observer.observe(document.body, { childList: true, subtree: true });
