# Changelog

## 2.0.0 (2026-11-07)
- Stable package based on rc4, with no functional code changes.
- User-reported successful tests on Joomla 5.4.x and 6.2.x (installation, list and edit reset actions, view retention, compact button).

## 2.0.0-rc4 (2026-11-07)
- Kept the current backend list view (`articles` or `featured`) after single and bulk resets, using an allowlisted redirect parameter.
- Reduced only the per-row list reset button to 25 × 25 CSS pixels without shrinking its icon.
- Kept the article edit button, Bootstrap confirmation modal, CSRF, ACL and database reset logic unchanged.
- rc3 reported working on Joomla 5.4.x and Joomla 6.2; rc4 requires testing of the two adjustments.

## 2.0.0-rc3 (2026-11-07)
- Restored compact original-style visual integration in article edit form and list, without jQuery or replacing the original input.
- Added a reusable Bootstrap 5 confirmation modal with localized labels, keeping a browser confirmation fallback.
- Explicitly registers Joomla `bootstrap.modal` script via the Web Asset Manager.
- Keeps the secure rc2 POST/ACL/CSRF reset handler untouched.
- rc2 was reported working on Joomla 5.4.x and Joomla 6.2; rc3 requires fresh runtime testing.

## 2.0.0-rc2 (2026-11-07)
- Fixed fatal error on reset: database is now injected via the service provider (DatabaseAwareTrait), instead of calling protected CMSApplication::getContainer().
- Joomla 5/6 runtime verification remains pending.

## 2.0.0-rc1 (2026-11-07)
- Rebuilt as Joomla event subscriber for Joomla 5 and 6.
- Replaced GET side effects with authenticated POST + CSRF token.
- Added per-article authorization and bounded selection.
- Replaced jQuery/modal inline script with independent vanilla JavaScript.
- Removed problematic original installer script and external update-server link.
- Added German translations and test checklist.
- Test candidate: no Joomla runtime acceptance test yet.

## 1.3.1 (original UWiX release)
- Original extension used as the basis for this fork.
