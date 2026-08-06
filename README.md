# Email Signatures RTD

**Contributors:** Webfor Agency  
**Tags:** email, signature, template, team  
**Requires at least:** 5.0  
**Tested up to:** 6.8  
**Stable tag:** 1.0.0  
**License:** GPL v2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin for managing professional email signature templates with global styles, custom fields, and automated image generation.

## Description

Email Signatures Pro allows you to create and manage standardized email signatures for your team members. The plugin generates multiple versions of each signature (with/without phone numbers, different layouts) and automatically creates PNG images that can be easily copied into email clients.

## Features

- **Custom Post Type**: Dedicated "Signatures" post type for managing individual email signatures
- **Global Style Management**: Configure fonts, colors, images, and social links that apply to all signatures
- **Custom Fields**: 
  - Title/Position
  - Phone Number
  - Meeting Link URL
- **Automatic Image Generation**: Creates multiple PNG versions of each signature:
  - Full signature with name
  - With title only
  - With phone number
  - Phone number only
  - Website link
- **Featured Image Support**: Upload profile photos for each signature
- **Frontend Display**: Protected signature pages viewable only by logged-in users
- **Social Media Integration**: Add and manage social media icons with gradient color tinting
- **Regeneration**: Ability to regenerate signature images when content changes
- **Automatic Updates**: GitHub-based automatic updates via Plugin Update Checker

## Installation

### Manual Installation

1. Download the plugin files
2. Upload the `email-signatures-rtd` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to **Signatures** → **Settings** to configure global options

### From GitHub

This plugin supports automatic updates from GitHub. Once installed, it will check for new releases and notify you when updates are available.

## Configuration

### Global Settings

Access the settings page at **Signatures** → **Settings**. The settings are organized into tabs:

#### Fonts Tab
- **Fonts Embed URL**: Google Fonts or custom font embed URL
- **Heading Font CSS Family**: CSS font-family value for headings
- **Body Font CSS Family**: CSS font-family value for body text

#### Colors Tab
- **Primary Color**: Main brand color
- **Secondary Color**: Secondary brand color
- **Tertiary Color**: Accent color
- **Neutral Color**: Neutral/gray color

#### Images Tab
- **Default Avatar**: Fallback profile image
- **Company Logo**: Your company logo
- **CTA Button Image**: Call-to-action button graphic

#### Social Links Tab
- Add multiple social media links with custom icons
- Icons are automatically tinted to match your brand colors
- Drag to reorder links

#### General Tab
- **Signature Website URL**: Company or team website
- **Office Phone Number**: Main office phone number

### Creating Signatures

1. Go to **Signatures** → **Add New**
2. Enter the person's name as the title
3. Upload a profile photo (Featured Image)
4. Fill in custom fields:
   - Title/Position
   - Phone Number
   - Meeting Link URL
5. Publish the signature

The plugin will automatically generate the signature page with multiple downloadable image variations.

## Usage

### Viewing Signatures

Each signature has a unique URL: `yoursite.com/signature/person-name/`

Signatures are protected and require users to be logged in to view them.

### Copying Signatures

On the signature page, users can:
- Copy the signature to clipboard
- Download individual PNG images for different use cases
- View the signature in different layouts

### Regenerating Signatures

If you update global settings or signature details:
1. Edit the signature
2. Click the "Regenerate Signature Images" button
3. Visit the signature page to generate fresh images

## Technical Details

### Custom Post Type

- **Post Type**: `signature`
- **Slug**: `/signature/`
- **Supports**: Title, Featured Image
- **Gutenberg**: Disabled (uses classic editor)
- **Public Access**: Protected (requires login)
- **SEO**: Automatically noindexed

### Custom Meta Fields

- `_esp_job_title`: Title/Position
- `_esp_phone_number`: Phone Number
- `_esp_meeting_url`: Meeting Link URL
- `_esp_signature_image_name`: Generated name image attachment ID
- `_esp_signature_image_title`: Generated title image attachment ID
- `_esp_signature_image_phone`: Generated phone image attachment ID
- `_esp_signature_image_phone_only`: Generated phone-only image attachment ID
- `_esp_signature_image_site`: Generated site image attachment ID

### AJAX Endpoints

- `esp_upload_signature_image`: Handle signature image uploads
- `esp_regenerate_signature`: Clear cached signature images

### Updates

The plugin uses [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) (v5.4) to provide automatic updates from GitHub releases.

To check for updates manually:
1. Go to **Plugins** page
2. Find "Email Signatures Pro"
3. Click "Check for Updates" in the plugin row

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- GD Library (for image manipulation)

## Changelog

### Version 1.2.1
- Minor bug fixes and improvements
- Enhanced script dependency handling

### Version 1.2.0
- Converted all jQuery code to vanilla JavaScript
- Added unsaved changes warning system for settings page
- Improved change detection for color pickers, image uploads, and form fields
- Added auto-dismissing success message after saving settings
- Fixed WordPress coding standards compliance issues
- Enhanced security with proper input sanitization and escaping
- Replaced deprecated functions (unlink to wp_delete_file)
- Added HTML5 drag-and-drop for sortable social links

### Version 1.1.3
- Implemented plugin-update-checker with direct folder structure
- Updated plugin metadata to match organizational standards
- Added comprehensive README documentation
- Removed composer dependencies in favor of bundled update checker
- Enhanced update checking functionality

### Version 1.1.2
- Updated to use plugin-update-checker folder structure
- Improved metadata and documentation
- Enhanced GitHub update integration

## Support

For issues, feature requests, or questions, please [open an issue](https://github.com/markfenske84/email-signatures-pro/issues) on GitHub.

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
│   ├── css/
│   │   └── esp-admin.css
│   └── js/
│       └── esp-admin.js
├── plugin-update-checker/
│   └── [library files]
├── templates/
│   └── single-signature.php
├── email-signatures-rtd.php
└── README.md
```

### Hooks & Filters

The plugin provides various WordPress hooks for customization:

- `esp_signature_template`: Filter signature template path
- Standard WordPress hooks for post types, meta boxes, and admin pages

## Roadmap

Potential future enhancements:

- [ ] Signature template variations
- [ ] Export/import signature settings
- [ ] Bulk signature generation
- [ ] Email client-specific formats
- [ ] REST API endpoints
- [ ] Shortcode support for embedding signatures

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

