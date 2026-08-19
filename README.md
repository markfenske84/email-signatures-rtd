# Email Signatures RTD

**Contributors:** Webfor Agency  
**Tags:** email, signature, template, team  
**Requires at least:** 5.0  
**Tested up to:** 6.8  
**Stable tag:** 1.0.6  
**License:** GPL v2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin for RTD Logistics email signatures — one fixed brand layout per team member, with copy-to-clipboard support.

## Description

Email Signatures RTD lets you create and manage standardized email signatures for RTD Logistics team members. Each signature uses a locked RTD brand design (colors, fonts, logo, and website are built into the plugin). The plugin automatically generates PNG images for email clients and provides a one-click copy workflow.

## Features

- **Custom Post Type**: Dedicated "Signatures" post type for managing individual email signatures
- **Per-signature fields**:
  - Name (post title)
  - Title / Position
  - Phone Number
  - Featured Image (avatar)
- **Edit Screen Links**: View Signature and Preview Signature (unsaved) from the publish box
- **Email-Safe Output**: Centralized renderer produces table + inline-styled PNG images only for clipboard copy
- **Avatar Crop**: Hard-cropped `esp-avatar` image size (172×172) for consistent circular photos
- **Frontend Display**: Protected signature pages viewable only by logged-in users
- **Regeneration**: Ability to regenerate signature images when content changes
- **Automatic Updates**: GitHub-based automatic updates via Plugin Update Checker

## Installation

### Manual Installation

1. Download the plugin files
2. Upload the `email-signatures-rtd` folder to `/wp-content/plugins/`
3. Activate the plugin through the **Plugins** menu in WordPress

### From GitHub

This plugin supports automatic updates from GitHub. Once installed, it will check for new releases and notify you when updates are available.

## Creating Signatures

1. Go to **Signatures** → **Add New**
2. Enter the person's name as the title
3. Upload a profile photo (Featured Image)
4. Fill in:
   - Title / Position
   - Phone Number
5. Publish the signature

The plugin will automatically generate the signature preview page and PNG assets on first view.

## Usage

### Viewing Signatures

Each signature has a unique URL: `yoursite.com/signature/person-name/`

Signatures are protected and require users to be logged in to view them.

### Copying Signatures

On the signature page, click **Copy Signature** to copy email-safe HTML (tables and PNG images only) to your clipboard once all images have been generated. Copy is blocked until generation completes.

### Preview Without Saving

On the signature edit screen, click **Preview Signature** to open a new tab with the full pipeline (PNG generation + Copy) using current form values without saving the post.

### Regenerating Signatures

If you update signature details:

1. Edit the signature and save, or click **Regenerate Signature** on the preview page
2. Visit the signature page to generate fresh images

## Technical Details

### Custom Post Type

- **Post Type**: `signature`
- **Slug**: `/signature/`
- **Supports**: Title, Featured Image
- **Gutenberg**: Disabled (uses classic editor)
- **Public Access**: Protected (requires login)
- **SEO**: Automatically noindexed

### Custom Meta Fields

- `_esp_job_title`: Title / Position
- `_esp_phone_number`: Phone Number
- `_esp_signature_image_header`: Generated header image attachment ID
- `_esp_signature_image_phone`: Generated phone image attachment ID
- `_esp_signature_image_site`: Generated site image attachment ID

### AJAX Endpoints

- `esp_upload_signature_image`: Handle signature image uploads
- `esp_regenerate_signature`: Clear cached signature images
- `esp_stage_preview`: Stage unsaved preview data in a transient
- `esp_get_signature_html`: Return email-safe signature HTML for in-place DOM swap

### Updates

The plugin uses [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) (v5.4) to provide automatic updates from the GitHub `main` branch.

To check for updates manually:

1. Go to the **Plugins** page
2. Find "Email Signatures RTD"
3. Click "Check for Updates" in the plugin row

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher

## Changelog

### Version 1.0.6
- Fix Copy Signature button staying hidden after regenerating: the button is created by script once generation finishes, and was still matching the no-JS `display:none` rule

### Version 1.0.5
- Fix blank signatures after generation: PNGs saved in 1.0.4 had no stored dimensions, so the email markup rendered them at 0×0
- Generated PNGs now record their width and height on upload, without the slow full metadata pass
- Existing signatures repair themselves on the next page load; no regeneration required
- A PNG with unreadable dimensions is now treated as missing and regenerated instead of rendering empty
- View Signature and Preview Signature moved below the post title as text links
- Confirmation prompt before regenerating, or before saving changes that replace an active signature's images

### Version 1.0.4
- View Signature and Preview Signature links on the edit screen publish box
- Unsaved preview flow with full PNG generation and Copy via user transients
- Centralized email-safe HTML renderer (`signature-email.php` partial)
- Avatar hard crop (`esp-avatar` 172×172) for non-square featured images
- Conditional PNG cache invalidation (only when signature fields change)
- Faster generation: skip attachment metadata, in-place DOM swap (no reload), self-hosted html2canvas
- Copy sanitization: block copy if divs remain, strip class attributes from clipboard HTML

### Version 1.0.3
- Redesigned signature preview page: left-aligned layout, actions below the signature, back link to edit screen
- Sharper logo via 2× asset displayed at standard size
- Sharper avatar: larger source image, `<img>` element for html2canvas capture, preload before rasterizing

### Version 1.0.2
- Fix update checker to track the `main` branch instead of legacy git tags

### Version 1.0.1
- Testing release to verify GitHub-based plugin updates from the `main` branch

### Version 1.0.0
- RTD Logistics signature template redesign (Outfit + Red Hat Mono, fixed 380×164 layout)
- Simplified admin: per-signature fields only (title, phone, avatar); settings page removed
- Hardcoded brand colors, fonts, logo, and website URL

## Support

For issues, feature requests, or questions, please [open an issue](https://github.com/markfenske84/email-signatures-rtd/issues) on GitHub.

## License

This plugin is licensed under the GPL v2 or later.

```
Copyright (C) 2024 Webfor Agency

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## Credits

**Author**: Webfor Agency  
**Website**: [https://webfor.com](https://webfor.com)

Uses [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) by Yahnis Elsts (MIT License)

## Development

### File Structure

```
email-signatures-rtd/
├── assets/
│   ├── imgs/
│   │   ├── rtd-logo.png
│   │   └── rtd-logo@2x.png
│   └── js/
│       ├── esp-admin.js
│       └── html2canvas.min.js
├── includes/
│   └── esp-signature-render.php
├── plugin-update-checker/
│   └── [library files]
├── templates/
│   ├── partials/
│   │   ├── signature-capture.php
│   │   └── signature-email.php
│   └── single-signature.php
├── email-signatures-rtd.php
└── README.md
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.
