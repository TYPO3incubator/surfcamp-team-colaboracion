import { html, LitElement, nothing } from 'lit';

class PresenceBadge extends LitElement {
  static properties = {
    count: { type: Number },
    avatarUrl: { type: String, attribute: 'avatar-url' },
  };

  constructor() {
    super();
    this.count = 0;
    this.avatarUrl = '';
  }

  createRenderRoot() {
    return this;
  }

  render() {
    if (this.count === 0) {
      return nothing;
    }

    return html`
      <div class="collaboration-badge-mini" title="${this.count} user(s) editing">
        <div class="collaboration-badge-mini__icon">
          ${this.avatarUrl
            ? html`<img class="collaboration-badge-mini__avatar"
                        src="${this.avatarUrl}"
                        alt="" />`
            : html`<typo3-backend-icon identifier="status-user-backend"
                                       size="small"></typo3-backend-icon>`
          }
        </div>
        ${this.count > 1
          ? html`<span class="collaboration-badge-mini__count">${this.count}</span>`
          : nothing
        }
      </div>
    `;
  }
}

customElements.define('typo3-collaboration-badge', PresenceBadge);
