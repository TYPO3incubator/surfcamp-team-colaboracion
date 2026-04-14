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

    return html`
      <div class="collaboration-presence-badge ${isCurrentUser ? 'collaboration-presence-badge--self' : ''}"
           style="z-index: ${zIndex}"
           title="${user.displayName}">
        <div class="collaboration-presence-badge__button">
          ${user.avatarUrl
            ? html`<img class="collaboration-presence-badge__avatar"
                        src="${user.avatarUrl}"
                        alt="${user.displayName}" />`
            : html`<typo3-backend-icon identifier="status-user-backend"
                                       size="small"></typo3-backend-icon>`
          }
        </div>
        <span class="collaboration-presence-badge__initial">${initial}</span>
      </div>
    `;
  }
}

customElements.define('typo3-collaboration-avatars', PresenceAvatars);
