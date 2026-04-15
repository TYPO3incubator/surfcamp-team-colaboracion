import AjaxRequest from "@typo3/core/ajax/ajax-request.js";
import { LitElement, html } from 'lit';
import Modal, { Types, Sizes, Positions } from '@typo3/backend/modal.js';

export class ContextualHistoryTriggerElement extends LitElement {
    static properties = {
        url: {type: String, attribute: 'url'},
    };

    async buttonActivated() {
        Modal.advanced({
            type: Types.iframe,
            title: '',
            content: this.url,
            size: Sizes.expand,
            position: Positions.sheet,
        });
    }

    render() {
        return html`<button @click="${this.buttonActivated}">Test</button>`;
    }
}
customElements.define('typo3-backend-contextual-history-trigger', ContextualHistoryTriggerElement);

