import { html, LitElement, nothing } from 'lit';

class PresenceBadge extends LitElement {
  static properties = {
    count: { type: Number },
    recordId: { type: String, attribute: 'record-id' },
    _users: { type: Array, state: true },
  };

  constructor() {
    super();
    this.count = 0;
    this.recordId = '';
    this._users = [];
    this._showTimeout = null;
    this._bound = this._onPresenceUpdate.bind(this);
  }

  connectedCallback() {
    super.connectedCallback();
    document.addEventListener('collaboration:presence-update', this._bound);
  }

  disconnectedCallback() {
    super.disconnectedCallback();
    clearTimeout(this._showTimeout);
    document.removeEventListener('collaboration:presence-update', this._bound);
  }

  _onPresenceUpdate(e) {
    if (!this.recordId || !e.detail.editingRecords) {
      return;
    }
    const record = e.detail.editingRecords[this.recordId];
    const newUsers = record ? record.users : [];
    const newCount = record ? record.count : 0;
    // JSON.stringify guard: only update if data actually changed
    if (JSON.stringify(newUsers) !== JSON.stringify(this._users)) {
      this._users = newUsers;
    }
    if (newCount !== this.count) {
      this.count = newCount;
    }
  }

  createRenderRoot() {
    return this;
  }

  _cssIdent() {
    return this.recordId.replace(/:/g, '-');
  }

  _buildAriaLabel() {
    if (this._users.length === 0) {
      return `${this.count} user(s) editing`;
    }
    return this._users
      .map((u) => `${u.displayName} bearbeitet ${u.activeField || 'dieses Element'}`)
      .join(', ');
  }

  _getPopover() {
    return this.querySelector('.collaboration-badge-hover-popover');
  }

  _showPopover() {
    clearTimeout(this._showTimeout);
    this._showTimeout = setTimeout(() => {
      const popover = this._getPopover();
      if (popover && !popover.matches(':popover-open')) {
        popover.showPopover();
      }
    }, 200);
  }

  _hidePopover() {
    clearTimeout(this._showTimeout);
    const popover = this._getPopover();
    if (popover && popover.matches(':popover-open')) {
      popover.hidePopover();
    }
  }

  render() {
    if (this.count === 0) {
      return nothing;
    }

    const popoverId = `badge-popover-${this._cssIdent()}`;
    const firstUser = this._users[0];

    return html`
      <div class="collaboration-badge-mini"
           tabindex="0"
           aria-label="${this._buildAriaLabel()}"
           @mouseenter="${this._showPopover}"
           @mouseleave="${this._hidePopover}"
           @focus="${this._showPopover}"
           @blur="${this._hidePopover}"
           style="anchor-name: --badge-${this._cssIdent()}">
        <div class="collaboration-badge-mini__icon">
          ${firstUser?.avatarUrl
            ? html`<img class="collaboration-badge-mini__avatar"
                        src="${firstUser.avatarUrl}"
                        alt="" />`
            : html`<typo3-backend-icon identifier="status-user-backend"
                                       size="small"></typo3-backend-icon>`
          }
        </div>
        ${this.count > 1
          ? html`<span class="collaboration-badge-mini__count">${this.count}</span>`
          : nothing
        }
        ${this._renderHoverPopover(popoverId)}
      </div>
    `;
  }

  _renderHoverPopover(popoverId) {
    if (this._users.length === 0) {
      return nothing;
    }

    return html`
      <div class="collaboration-badge-hover-popover"
           id="${popoverId}"
           popover="manual"
           style="position-anchor: --badge-${this._cssIdent()}">
        ${this._users.map((user) => html`
          <div class="collaboration-badge-hover-popover__user ${user.idle ? 'collaboration-badge-hover-popover__user--idle' : ''}">
            <span class="collaboration-badge-hover-popover__name">${user.displayName}</span>
            ${user.activeField
              ? html`<span class="collaboration-badge-hover-popover__field">Feld: ${user.activeField}</span>`
              : nothing
            }
          </div>
        `)}
      </div>
    `;
  }
}

customElements.define('typo3-collaboration-badge', PresenceBadge);
