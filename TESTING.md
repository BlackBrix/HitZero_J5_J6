# Manual test checklist

1. Back up the Joomla site/database; install/upgrade and enable plugin.
2. Disable the Joomla 6 compatibility plugin in the Joomla 6 test site.
3. Open **Content → Articles** with the Hits column enabled. Verify icon-only compact buttons for >0 hits and disabled buttons for zero.
4. Reset one test article via the Bootstrap modal, confirm POST, success message, and database `#__content.hits = 0`.
5. Select 2+ articles, open Actions menu, run reset, confirm counts and errors.
6. Open an existing article → Publishing → Hits; confirm original input + attached button in a single input-group. Test reset button and modal unsaved edit warning.
7. Run as account without article edit rights: no actionable button and forged POST must be denied.
8. Run as author with `core.edit.own` and verify own article only.
9. Test GET to `index.php?option=com_content&hitzero_action=reset` and POST without token: no database changes.
10. Test selection >500, invalid IDs and nonexistent IDs; test featured list, pagination and alternative administrator templates.
11. Inspect PHP error logs and browser console on Joomla 5.4 and Joomla 6.2. Verify modal Cancel and (X) leave hit counts unchanged, and no JavaScript errors or duplicate buttons after normal navigation.

## rc4 regression checks

- In `view=articles`, reset one and multiple article hit counters: stay in `view=articles`.
- In `view=featured`, reset one and multiple article hit counters: stay in `view=featured`.
- Check that existing filters are retained after resetting.
- Check list row reset button at 25 × 25 px; edit-form button must be unchanged.
- Run on Joomla 5.4.x and Joomla 6.2.x.

## Stable release validation (2026-11-07)

The maintainer reports rc4 working on both Joomla 5.4.x and Joomla 6.2.x, following functional verification of the three reset workflows. rc4 also addressed compact list buttons and return-to-view behavior. The stable 2.0.0 package contains no runtime code changes relative to rc4.
