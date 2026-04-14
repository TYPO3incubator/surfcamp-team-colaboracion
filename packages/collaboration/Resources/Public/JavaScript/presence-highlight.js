/**
 * Scans the page module for collaboration editing indicators and:
 * 1. Adds the border highlight class to parent content elements
 * 2. Moves the editing-info bar from inside t3-page-ce-body to between header and body
 *
 * Side-effect module — loaded via AddPresenceToDocHeaderListener.
 */
function applyEditingHighlights() {
  document.querySelectorAll('.t3-page-ce-body .t3-page-ce-editing-info').forEach((info) => {
    const ceElement = info.closest('.t3-page-ce-element');
    if (!ceElement) {
      return;
    }

    // Add border highlight to the wrapper
    const ceWrapper = ceElement.closest('.t3-page-ce');
    if (ceWrapper) {
      ceWrapper.classList.add('t3-page-ce--collaboration-editing');
    }

    // Move editing-info between header and body
    const body = ceElement.querySelector('.t3-page-ce-body');
    if (body) {
      ceElement.insertBefore(info, body);
    }
  });
}

// ES modules are always deferred, so DOM is ready at this point
applyEditingHighlights();

// Observe for dynamically loaded content (drag & drop, AJAX reloads)
const observer = new MutationObserver(() => {
  applyEditingHighlights();
});
observer.observe(document.body, { childList: true, subtree: true });
