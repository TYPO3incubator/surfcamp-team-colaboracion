Update
======

When updating `EXT:collaboration` to a newer version, please follow these steps:

1.  **Update via Composer**: Run `composer update typo3-incubator/collaboration`.
2.  **Clear Caches**: Clear the TYPO3 system caches in the install tool or via CLI.
3.  **Check Release Notes**: Review the release notes for any specific update instructions or breaking changes.
4.  **Database Updates**: If the extension includes new database fields or changes (e.g., to `sys_note`), run the database compare tool in the Maintenance module.
