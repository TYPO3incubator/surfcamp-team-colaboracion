import { html, LitElement, nothing } from 'lit';
import { mockPresenceData } from '@typo3/collaboration/mock-data.js';

class PresenceAvatars extends LitElement {
  static properties = {
    users: { type: Array },
    currentUserUid: { type: Number, attribute: 'current-user-uid' },
  };

  constructor() {
    super();
    this.users = [];
    this.currentUserUid = 0;
  }

  connectedCallback() {
    super.connectedCallback();
    if (this.users.length === 0) {
      this.users = mockPresenceData.pageUsers;
      this.currentUserUid = mockPresenceData.currentUser.uid;
    }
  }

  createRenderRoot() {
    return this;
  }

  render() {
    if (this.users.length === 0) {
      return nothing;
    }

    return html`
      <div class="collaboration-presence-avatars"
           role="group"
           aria-label="${this.users.length} users are currently on this page">
        ${this.users.map((user, index) => this._renderAvatar(user, index))}
      </div>
    `;
  }

  _renderAvatar(user, index) {
    const isCurrentUser = user.uid === this.currentUserUid;
    const initial = user.displayName.charAt(0).toUpperCase();
    const zIndex = this.users.length - index;
    const popoverId = `presence-popover-${user.uid}`;
    const anchorName = `--avatar-${user.uid}`;

    return html`
      <div class="collaboration-presence-badge ${isCurrentUser ? 'collaboration-presence-badge--self' : ''} ${user.idle ? 'collaboration-presence-badge--idle' : ''}"
           style="z-index: ${zIndex}">
        <button class="collaboration-presence-badge__button"
                popovertarget="${popoverId}"
                style="anchor-name: ${anchorName}"
                aria-label="${user.displayName}">
          ${user.avatarUrl
            ? html`<img class="collaboration-presence-badge__avatar"
                        src="${user.avatarUrl}"
                        alt="${user.displayName}" />`
            : html`<typo3-backend-icon identifier="status-user-backend"
                                       size="small"></typo3-backend-icon>`
          }
        </button>
        <span class="collaboration-presence-badge__initial">${initial}</span>
        ${this._renderPopover(user, popoverId, anchorName)}
      </div>
    `;
  }

  _renderPopover(user, popoverId, anchorName) {
    const moduleLabel = this._getModuleLabel(user.module);
    const elementLabel = user.editingElement || 'Browsing';
    const sinceLabel = user.activeSince != null ? `seit ${user.activeSince} Min.` : '';
    const idleLabel = user.idle ? 'inaktiv' : 'aktiv';
    const idleClass = user.idle ? 'collaboration-popover__status--idle' : 'collaboration-popover__status--active';

    return html`
      <div class="collaboration-presence-popover"
           id="${popoverId}"
           popover="auto"
           style="position-anchor: ${anchorName}">
        <div class="collaboration-popover__header">
          <div class="collaboration-popover__avatar">
            ${user.avatarUrl
              ? html`<img src="${user.avatarUrl}" alt="${user.displayName}" />`
              : html`<typo3-backend-icon identifier="status-user-backend"
                                       size="small"></typo3-backend-icon>`
            }
          </div>
          <div class="collaboration-popover__info">
            <div class="collaboration-popover__name">${user.displayName}</div>
            <div class="collaboration-popover__detail">${moduleLabel} · ${elementLabel}</div>
          </div>
        </div>
        <div class="collaboration-popover__footer">
          <span class="collaboration-popover__since">${sinceLabel}</span>
          <span class="collaboration-popover__status ${idleClass}">
            <typo3-backend-icon identifier="${user.idle ? 'actions-circle' : 'actions-circle-full'}"
                                size="small"></typo3-backend-icon>
            ${idleLabel}
          </span>
        </div>
      </div>
    `;
  }

  _getModuleLabel(module) {
    const labels = {
      layout: 'Layout-Modul',
      records: 'Records-Modul',
      preview: 'Preview',
    };
    return labels[module] || module || 'Unbekannt';
  }
}

customElements.define('typo3-collaboration-avatars', PresenceAvatars);
