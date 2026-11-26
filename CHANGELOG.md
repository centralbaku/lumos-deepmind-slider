# Changelog

All notable changes to the **Lumos DeepMind Hero Slider** plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.1] - 2026-10-05

### Added
- **Custom Slide Logo (SVG or PNG only)**:
  - Add an optional brand logo or mark directly above the headline or below description/buttons per slide (e.g. Google DeepMind 4-point sparkle).
  - Strict format enforcement: only vector SVG (`.svg`) or PNG (`.png`) are permitted; all other formats are rejected.
  - Position selector: **On Top of Text** (above headline) or **Bottom of Text** (below description and action buttons).
  - Aligns responsively with text layout (centered on Hero layout, left-anchored on Card layout).
  - Full support across slide export/import bundling into the ZIP archive.
- **Video Loading > 50% Buffer Crossfade & Continuous Loop**:
  - Displays fallback poster image while background video streams.
  - Automatically detects when video reaches `> 50%` loaded in buffer (`video.buffered` TimeRanges check or `readyState >= 4`).
  - Seamlessly crossfades fallback poster (`opacity: 1 -> 0`) to video (`opacity: 0 -> 1`) over 0.75s.
  - Video begins playback immediately as crossfade starts, then continues into seamless loop playback without reverting to poster image.
- **Slide & Media Export System (.zip & .json)**:
  - 1-click export packaging all slider parameters, texts, headings, descriptions, layouts, button settings, active statuses, and global settings into `slides.json`.
  - Bundles all uploaded background videos (MP4, WebM), poster fallbacks, and images into a dedicated `media/` directory inside the `.zip` archive.
  - Automatic media deduplication to ensure shared assets are only included once.
  - Quick lightweight `.json` export option for configuration-only backups.
- **Smart Import with WordPress Media Library Sideloading**:
  - Drag-and-drop upload for exported `.zip` packages and `.json` files.
  - Automatic extraction and registration of bundled media files into the WordPress Media Library with full attachment metadata via `wp_upload_bits()` and `wp_insert_attachment()`.
  - Automatic remapping of slide video, poster, image, and logo URLs to the newly registered WordPress attachment URLs.
  - Choice of Import Mode: **Replace existing slides** or **Append to existing slides** with automatic ID re-keying.
  - Optional global slider settings import (autoplay, speed, loop, video advance, scrim opacity, slide dimensions).
- **Export & Import in Standalone Interactive Demo (`demo/admin.html`)**:
  - Full client-side ZIP archive packaging and unbundling powered by JSZip.
  - Instant Blob URL generation for live in-browser preview without requiring a backend server.
- **Automatic Slide Transition on Video End**: Slide automatically advances to the next slide when background video playback completes (`ended` event listener).
- **Customizable Autoplay Timer**: Added manual duration input (e.g. 6 seconds) with real-time validation in admin settings, shortcode parameters, and data attributes.
- **Standalone Slider Live Preview**: Added a prominent **Preview Slider** button in the admin header opening a distraction-free page with zero theme wrappers, headers, or footers to test the carousel in isolation (`?lumos_slider_preview=1`).
- **Comprehensive Media Specifications Guide**:
  - Built-in admin tab and in-modal callout banner for recommended media formats and resolutions.
  - Video specs: WebM (VP9) + MP4 (H.264), 16:9 aspect ratio, 1920x1080 (Full HD), 24-30 fps, under 10MB.
  - Image specs: WebP / JPG, 1920x1080 or 2560x1440, under 400KB.
  - One-click copyable FFmpeg terminal commands for stripping audio tracks and enabling FastStart streaming.
- **In-Admin Changelog Screen**: Added dedicated Changelog tab and sub-menu in the WordPress admin with full version history and upgrade guidance.
- **Automatic Directory Normalization (`upgrader_source_selection`)**: Ensures that zip archives named with versions (e.g. `lumos-deepmind-slider-v.1.0.1.zip`) or exported from GitHub automatically map to the canonical `lumos-deepmind-slider` directory, enabling native WordPress in-place replacements without duplicate folders.

### Changed
- **Full-Width Admin & Expanded Slide Editor**: Redesigned `.lumos-admin-wrap` to utilize 100% of the screen width and expanded the slide edit modal (`.lumos-modal-window`) to 1060px for a comfortable, spacious editing experience.
- **Modern Admin UI & Button Styling**: Overrode default boxy WordPress buttons with refined Google DeepMind SaaS styling (40px height, 8px radius, aligned Dashicons, `#1a73e8` primary blue, smooth elevation shadows).
- **Slide Action Buttons**: Redesigned Edit, Duplicate, and Delete buttons with clean card borders and a red destructive hover accent for Delete.
- **Headline Style Tag Auto-Sync**: Selecting a layout card in the modal now automatically synchronizes the Recommended Heading Tag (`H1` for Hero Style, `H2` for Card Style).
- **Support for `#` Hash Anchors and Relative URLs**: Converted link inputs to text fields, allowing `#`, `#contact`, and `/relative-path` URLs without HTML5 browser validation errors (`Please enter a URL.`).
- **Page Jump Prevention**: Added frontend click interceptor on `a[href="#"]` with `e.preventDefault()`, preventing dummy placeholder links from jumping the browser window to top:0.

### Fixed
- **Modal Layout Selection Badge Glitch**: Fixed visual bug where the layout selection badge (`Slide 1 Style`, `Slide 2 & 3 Style`) was overlapping or peeking behind the dark preview box. Reorganized into a clean `.lumos-layout-card-header` flex row.
- **Drag Rebound Issue**: Calibrated drag/swipe physics with a lower threshold (15% slide width / 60px minimum distance) and velocity calculation so slides complete forward transitions smoothly rather than snapping backward.

---

## [1.0.0] - 2026-10-03

### Added
- **DeepMind Hero Carousel**: Pixel-perfect recreation of Google DeepMind Gemini Robotics hero slider.
- **Slide Peeking**: Responsive multi-slide viewport (93.5% slide width on desktop, 88% on tablet, 86% on mobile).
- **Dual Layout Modes**:
  - **Hero Style (Centered)**: Title & description centered with primary CTA button anchored at bottom-center.
  - **Card Style (Top-Left)**: Title & description anchored top-left with compact action buttons grouped directly below.
- **Video & Image Backgrounds**: Background video (WebM/MP4) with fallback posters and responsive image picture tags.
- **Gradient Scrim Overlays**: Dark scrim and light scrim options for typography legibility.
- **WordPress Media Integration**: Native WordPress Media Library upload buttons for videos, posters, and images.
- **Shortcode & Gutenberg Block**: `[deepmind_slider]` and block editor widget.
- **Touch / Drag Gestures**: Smooth mouse drag and mobile swipe interactions.
