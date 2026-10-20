# Account settings

Authenticated users can open **Accountinstellingen** from the avatar menu to
manage their personal details, system alias, profile photo, Dutch/English
signatures, and password, with inner settings navigation but without the main
application section sidebar.
Email cannot be changed here. Administration and self-service both edit
`users.alias`, the single case-insensitive alternate login identifier.
Conflicting emails and aliases are rejected.

The implementation uses the existing `profiles` relationship and columns.
The additional nullable, unique `users.alias` column is introduced by
`2026_10_20_152516_add_alias_to_users_table.php`.

The subsequent consolidation migration copies profile usernames only where the
alias is empty, preserves existing aliases, and drops `profiles.username`.
Discarded usernames no longer work for login. Conflicting login identifiers
abort the migration without changing data. Back up the database first: this
consolidation cannot be reversed without restoring discarded usernames from a backup.

- Avatars are cropped with Cropper.js and re-encoded on the server as 256 × 256 PNGs.
- Signatures retain the legacy filenames `signatures/{user_id}.png` (Dutch) and
  `signatures/{user_id}En.png` (English). Each language can be replaced or deleted
  independently. Images are fitted within 1600 × 600 pixels without upscaling.
- Images use Laravel's private `local` disk and authenticated, owner-only
  endpoints. No public storage link is required. Existing signature files must
  be copied to the same relative filenames on that disk when migrating legacy
  assets; the settings page does not migrate files from the legacy application.
- Uploads accept JPG, PNG, GIF, and WebP, up to 5 MB and 4096 × 4096 pixels.
- Password changes reuse the Fortify password action, require the current
  password and confirmation, and are rate-limited.

After deploying, run migrations and build assets through Sail:

```sh
vendor/bin/sail artisan migrate --no-interaction
vendor/bin/sail npm ci
vendor/bin/sail npm run build
```

Run the focused regression suite with:

```sh
vendor/bin/sail artisan test --compact tests/Feature/AccountSettingsTest.php
```