# HitZero for Joomla 5 and 6

**Version 2.0.0 — stable release.** The maintainer reports successful installation and functional tests of release candidate rc4 on Joomla 5.4.x and Joomla 6.2.x.

Modernized fork of **HitZero 1.3.1 by UWiX**. Retains the purpose and original attribution. GPL-2.0-or-later. This is not an official UWiX release.

## Features

- Reset one article counter in the article list.
- Reset hit counters for selected articles from the Actions dropdown (or fallback toolbar button).
- Reset the counter from an existing article's Publishing form.
- English and German language files.
- No jQuery dependency; uses Joomla's ES/vanilla DOM interfaces.

## Security and behavior

- POST requests only and Joomla session CSRF verification.
- Administrator-only endpoint; each article requires Joomla `core.edit` or eligible `core.edit.own` permission.
- Strict positive article IDs and maximum 500 selections.
- Conditional database updates; no table locks, GET mutation or ad-hoc audit events.
- Editing form is never submitted by the reset action; **unsaved edits are lost** when returning to the edit page.
- There is no undo operation. Back up your database before testing.

## Installation

Install `plg_system_hitzero-2.0.0.zip` with **System → Install → Extensions**, then enable **System - HitZero**. Disable any old HitZero instance first. The new package uses the **same plugin element** (`system/hitzero`), so an installation of the same element will generally upgrade rather than create a second plugin. Back up first.

## Recommended post-installation verification

- Joomla 5.4.x, PHP 8.2+: installed/disabled/enabled and all 3 UI actions.
- Joomla 6.2.x, PHP 8.3+: **Backward Compatibility 6 disabled**, all 3 UI actions.
- Administrator list with normal, featured, filtered, pagination and alternative admin templates.
- Normal editor, author-only editor, user without edit rights; CSRF invalid and GET requests.
- 0 hits, positive hits, unknown ID, mixed authorized/non-authorized batch.
- With unsaved changes in article edit screen: confirmation warning and redirect.

## Changes delivered in rc4

- Keeps administrators on the same article list (`view=articles` or `view=featured`) after either list reset action.
- Reduces list row reset buttons to 25 × 25 px; the edit-form button is unchanged.
- Does not alter the secure reset logic or the Bootstrap modal.

## Changes delivered in rc3

- Wraps the **existing** readonly Hits input in a compact Bootstrap `input-group` with an icon-only reset button.
- Uses compact icon-only per-row reset buttons; keeps readable text for the bulk Actions menu.
- Uses a Bootstrap 5 modal confirmation dialog via Joomla Web Asset Manager (`bootstrap.modal`).
- Keeps the rc2 server-side POST, CSRF, article authorization and database reset logic unchanged.
- User-reported functional testing completed on Joomla 5.4.x and 6.2.x.

## Changes delivered in rc2

- Fixed fatal `CMSApplication::getContainer()` access by injecting `DatabaseInterface` through the plugin service provider.
- Previous rc2: the user reported that all three UI actions work on both Joomla 5.4.x and 6.2; hidden article and permission scenarios also passed on Joomla 6.2.

## Known constraints

UI hooks depend on markup of the Joomla core article list/edit page and may require adjustment with other administrator templates. Confirmation normally uses a Bootstrap 5 modal; if the administrator template prevents Bootstrap from loading, it falls back to a browser confirmation. The multi-selection action resets only permitted entries and reports skipped ones. The stable package is based on rc4, which the maintainer reports tested successfully on Joomla 5.4.x and 6.2.x. Other environments and admin templates have not been independently verified.

## Credits

- **UWiX**: original HitZero 1.3.1 extension and image artwork.
- Community modernization: Joomla 5/6 code, CSRF + ACL and updated user interface.

For redistribution, retain original attribution and GPL-2.0-or-later licensing. See `LICENSE` and `CHANGELOG.md`.
