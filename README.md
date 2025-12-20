# BBAB Portal

Private admin portal plugin for Brad Wales' portfolio site.

## Description

Provides server-side authentication to protect the `/brads-portal/` page hierarchy. Unlike JavaScript-based login gates, this plugin uses WordPress's `template_redirect` hook to check authentication **before** any content is sent to the browser.

## Features

- Server-side access control (no JavaScript bypass possible)
- Protects parent page and all child pages automatically
- Redirects to wp-login.php with return URL
- Admin settings page for configuration
- Dashboard shortcodes for stats, recent entries, drafts, quick links
- WPForms integration for frontend portfolio entry creation

## Requirements

- PHP 8.0+
- WordPress 6.0+
- BBAB Portfolio plugin (for form integration)
- WPForms Elite + Post Submissions addon

## Protected URLs

- `/brads-portal/` - Main dashboard
- `/brads-portal/add-portfolio/` - Portfolio entry form
- Any future child pages automatically protected

## Shortcodes

- `[bbab_portal_stats]` - Portfolio statistics
- `[bbab_portal_recent]` - Recent portfolio entries
- `[bbab_portal_drafts]` - Draft entries
- `[bbab_portal_links]` - Quick links

## Changelog

### 1.0.0
- Initial release
- Server-side access control
- Admin settings page
- Dashboard shortcodes
- WPForms integration