# BuddyPress Characters

BuddyPress plugin that adds character profiles to member pages for One World by Night.

**Version:** 2.6.0
**Requires:** WordPress 6.0+ / PHP 7.4+ / BuddyPress

## Installation

Upload `bp-characters-x.x.x.zip` via **Plugins > Add New > Upload Plugin** and activate.

Two entry points exist: the root `bp-characters.php` monolith (standalone class) and the modular `bp-characters/bp-characters.php` with separate includes. Only one should be active.

## Changelog

### 2.6.0
- Removed dead stub files and empty JS
- Stripped comment bloat and redundant PHPDoc
- Normalized version across both entry points

### 2.5.1
- Mobile compatibility fixes
- Search integration for character meta
- Select2 creature type dropdown
