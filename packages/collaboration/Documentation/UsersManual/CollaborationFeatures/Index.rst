.. _collaborationFeatures:

=====================
Backend Collaboration
=====================

The extension introduces several features to the TYPO3 Backend that enhance collaboration.

Contextual History Button
=========================

In the **Page** and **Workspaces** modules, you will find a "Last edited" button in the top right button bar.

- **Information**: It shows the date and time of the last change.
- **Details**: Hovering over the button reveals who made the change.
- **Action**: Clicking the button takes you directly to the record's history view.

Real-time Notifications
=======================

If Server Sent Events (SSE) are configured and supported, you will receive notifications for:

- **Cache Cleared**: A message appears when the cache has been cleared, suggesting a reload.
- **Parallel Editing**: If another user starts editing the same record you are currently working on, a warning notification is displayed to prevent overwriting changes.
