.. _configuration-site-set:

============================
Configuration with Site Sets
============================

The Collaboration extension uses TYPO3 Site Sets for easy configuration.

How to use
==========

1.  Navigate to the **Site Management > Sites** module.
2.  Edit the configuration of your site.
3.  On the **Sets** tab, add the **Collaboration** set to the active sets.
4.  Save the configuration.

What the Site Set provides
==========================

The **Collaboration** site set (`typo3-incubator/collaboration`) includes:

- **TSconfig**: Automatically includes the necessary TSconfig for backend collaboration features, such as the contextual history button and event stream integration.
- **Middleware**: Ensures that the `ContextualEditEventMiddleware` is active for the backend.
- **Dependency Management**: Handles internal dependencies required for the collaboration features to work seamlessly.

By using the Site Set, you ensure that all parts of the extension are correctly initialized and integrated into your TYPO3 installation.
