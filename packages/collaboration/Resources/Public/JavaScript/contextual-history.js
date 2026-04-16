import { LitElement, html } from 'lit';
import Modal, { Types, Sizes, Positions } from '@typo3/backend/modal.js';

export class ContextualHistoryTriggerElement extends LitElement {
    static properties = {
        url: {type: String, attribute: 'url'},
        label: {type: String, attribute: 'label'},
    };

    createRenderRoot() {
        return this;
    }

    async buttonActivated() {
        const modal = Modal.advanced({
            type: Types.iframe,
            title: '',
            content: this.url,
            size: Sizes.expand,
            position: Positions.sheet
        });
    }

    render() {
        return html`<button @click="${this.buttonActivated}" class="btn btn-sm btn-borderless">${this.label}</button>`;
    }
}
customElements.define('typo3-backend-contextual-history-trigger', ContextualHistoryTriggerElement);
