.. _technicalDetails:

=================
Technical Details
=================

This section covers the technical implementation of the Collaboration extension.

Backend Components
==================

Event Listeners
---------------

- `AddHistoryButtonEvent`: Injected via `ModifyButtonBarEvent`, it adds the contextual history button to the Page and Workspaces modules.
- `ModifyPageLayoutContentEventListener`: Loads the necessary JavaScript for contextual history.

Middlewares
-----------

- `ContextualEditEventMiddleware`: Intercepts edit requests to trigger parallel editing notifications.

Services
--------

- `CollaborationEventService`: Manages backend collaboration events.
- `LockedRecordsService`: Provides information about record locks.
- `EventMessageService`: Handles the creation of event messages for the stream.

AJAX Controllers
----------------

- `AjaxController`: Handles general AJAX requests for collaboration.
- `StreamController`: Manages the Server Sent Events (SSE) stream.

TCA Overrides
=============

The extension overrides the `sys_note` (Internal Note) TCA to add the `assigned_id` field, allowing notes to be assigned to backend users.

JavaScript Modules
==================

- `@collaboration/event-stream/contextual-history.js`: Handles the client-side logic for the history button.
- `@collaboration/event-stream/event.js`: Manages SSE event reception and notification display.
- `@collaboration/event-stream/input.js`: Utility for input handling.
