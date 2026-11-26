# Lumos DeepMind Hero Slider for WordPress

A pixel-perfect, responsive WordPress hero carousel inspired by [Google DeepMind Gemini Robotics](https://deepmind.google/models/gemini-robotics/).

---

## ✨ Full Backend Feature Set

This plugin provides complete, intuitive backend controls under **WordPress Admin → DeepMind Slider**:

1. **Select Header Text Layout**:
   - **Hero Style (Slide 1)**: Centered title & description in slide viewport with primary CTA button anchored at the bottom center.
   - **Card Style (Slides 2, 3, 4)**: Title and description anchored top-left with action buttons placed directly below the description.
   - **Heading Tag Selector**: Choose `H1`, `H2`, or `H3` for semantic SEO.

2. **Edit Text & Custom Slide Logo**:
   - Slide Title / Headline
   - Heading tag (`H1`, `H2`, `H3`)
   - Subtitle / Description text
   - **Custom Slide Logo**: Optional brand mark or vector icon (e.g. Google DeepMind 4-point sparkle) displayed on top of the headline or below the description/buttons. Strictly accepts **SVG (.svg)** or **PNG (.png)** only.

3. **Turn On and Off Buttons**:
   - **Button 1 (Primary CTA)**: Independent ON/OFF toggle switch. When turned off, it is completely hidden from the slide. When turned on, configure label, link URL, open in new tab (`_blank`), style (*Solid White Pill* vs *Frosted Glass Pill*), and icon (*Google AI Studio ribbon*, *Arrow Right*, *External link*, *None*).
   - **Button 2 (Secondary CTA)**: Independent ON/OFF toggle switch. When turned on, configure label, link URL, open in new tab, style (*Frosted Glass Pill* vs *Text Link with Chevron*), and icon (*Chevron Arrow*, *External*, *None*).

4. **Change Video and Image Backgrounds**:
   - Switch between **🎬 Video Background** (autoplay, muted, playsinline MP4/WebM with fallback poster image) and **🖼️ Image Background** (high-resolution photo or illustration).
   - **> 50% Buffer Crossfade & Instant Playback**: Fallback poster image displays while video buffers. As soon as `> 50%` of the video is loaded (or readyState 4), it initiates a smooth 0.75s crossfade to the video, immediately starts playback, and continues looping without reverting to the poster.
   - **Automatic Transition on Video End**: If configured, when a video slide finishes playing, the carousel automatically advances to the next slide smoothly without jumping.
   - Full integration with **WordPress Media Library** (`wp.media`) for 1-click uploads and selections.
   - Scrim overlay selector (*Dark Scrim*, *Light Scrim*, or *None*) to ensure pristine text readability over any background.

5. **Turn On and Off Slides**:
   - Each slide features an instant iOS-style **Active / Off** toggle switch on its card and inside the editor modal.
   - Inactive slides are safely preserved in drafts but skipped during frontend rendering.

6. **Delete, Duplicate, Reorder, and Add Slides**:
   - **Add New Slide**: 1-click modal to create custom slides.
   - **Duplicate Slide**: Clone any existing slide with all its media, layouts, and buttons.
   - **Delete Slide**: Remove slides with confirmation.
   - **Drag-and-Drop Reorder**: Easily drag slides by the handle to change playback sequence.
   - **Restore Demo Slides**: 1-click reset to the 4 default Google DeepMind Gemini Robotics slides.

7. **Built-in Media Sizing & Format Guidelines**:
   - Contextual specifications banner & pills right inside the slide editor modal.
   - Recommended dimensions, formats, and file sizes displayed directly beneath each media URL field.
   - Dedicated **Media Guidelines** admin tab with complete technical comparison table, video encoding commands (FFmpeg & HandBrake), and browser autoplay compliance tips.

---

## 📐 Recommended Media Sizes, Video Formats & Guidelines

To ensure pristine visual quality matching Google DeepMind, instant buffering, and high Google PageSpeed scores, follow these specifications:

### Quick Reference Matrix

| Media Type | Recommended Dimensions | Aspect Ratio | Formats & Codecs | Optimal File Size | Max Limit | Audio & Playback Requirements |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **🎬 Background Video** | `1920 × 1080` (1080p Full HD) | `16:9` widescreen | **MP4** (H.264 / AVC) or **WebM** (VP9 / AV1) | **3 MB – 8 MB** | 15 MB | **Strictly Muted** (`muted playsinline`), 24/30 fps, FastStart (`+faststart`) |
| **🖼️ Fallback Poster** | `1920 × 1080` (Matches video) | `16:9` widescreen | **WebP** (recommended) or **JPG** | **< 200 – 300 KB** | 400 KB | Export 1st frame (`0.0s`) of video for zero-flicker loading |
| **🖼️ Background Image** | `1920 × 1080` (or `2560 × 1440` Retina) | `16:9` widescreen | **WebP** (best), **JPG**, or **AVIF** | **150 KB – 400 KB** | 600 KB | Enable built-in Dark Scrim overlay for light text contrast |

---

### 🎥 Video Encoding & Optimization Best Practices

1. **Why Videos Must Be Muted**:
   - Modern browsers (Apple Safari, iOS Safari, Google Chrome, Mozilla Firefox, Microsoft Edge) strictly block background videos from auto-playing if they contain an active audio track.
   - Removing or stripping the audio track guarantees compliance and reduces total file size by **15% to 20%**.

2. **Enable FastStart / Web Optimization**:
   - Normal MP4 files place video metadata (`moov atom`) at the end of the file, forcing the browser to download the entire video before playing frame 1.
   - Enabling **FastStart** moves the metadata atom to the front, allowing video playback to begin **instantaneously** as the first bytes stream in.

3. **Recommended FFmpeg Terminal Command (1-Line)**:
   ```bash
   ffmpeg -i raw_video.mov -c:v libx264 -crf 23 -preset slow -pix_fmt yuv420p -an -movflags +faststart hero-video.mp4
   ```
   - `-c:v libx264`: Universally compatible H.264 video codec (works on 100% of devices).
   - `-crf 23`: Optimal visual quality with low file weight.
   - `-an`: Strips audio track completely.
   - `-movflags +faststart`: Enables instant web progressive buffering.

4. **Recommended HandBrake (Free GUI) Settings**:
   - **Preset**: `Fast 1080p30`
   - **Format**: `MP4` with **Web Optimized** checked.
   - **Video Tab**: Codec `H.264 (x264)`, Framerate `30 fps (Peak Framerate)`, Constant Quality `RF 22 to 24`.
   - **Audio Tab**: Click **Clear Tracks** to remove all audio tracks.

---

### 🖼️ Image Optimization Tips

1. **Use Modern WebP Format**:
   - WebP yields **25%–35% smaller file sizes** compared to standard JPEG while preserving crisp details.
   - Free tools: [Squoosh.app](https://squoosh.app) (Google Chrome team) or [TinyPNG](https://tinypng.com).
2. **Aspect Ratio & Composition**:
   - Always upload images in **16:9 widescreen aspect ratio** (`1920 × 1080` or `2560 × 1440`).
   - The DeepMind hero container scales with `aspect-ratio: 16 / 9`. Using 16:9 prevents unexpected cropping or letterboxing.

---

## 📦 What's Included

```
lumos_slides/
├── lumos-deepmind-slider.zip         <-- Ready-to-upload WordPress Plugin ZIP!
├── lumos-deepmind-slider/            <-- WordPress Plugin source folder
│   ├── lumos-deepmind-slider.php     <-- Main plugin file
│   ├── includes/
│   │   ├── class-slider-defaults.php  <-- Default DeepMind Gemini Robotics slides
│   │   ├── class-slider-shortcode.php <-- Shortcode handler & frontend renderer
│   │   ├── class-slider-admin.php     <-- WP Admin Slides Manager & AJAX endpoints
│   │   └── class-slider-block.php     <-- Gutenberg Block integration
│   └── assets/
│       ├── css/
│       │   ├── deepmind-slider.css    <-- DeepMind carousel stylesheet
│       │   └── admin-slider.css       <-- Admin manager styles & toggle switches
│       └── js/
│           ├── deepmind-slider.js     <-- Physics carousel engine (Embla-inspired)
│           ├── admin-slider.js        <-- WP Media picker, layout cards & AJAX
│           └── block.js               <-- Gutenberg block editor script
└── demo/                              <-- Standalone Interactive Browser Demo
    ├── index.html                     <-- Live frontend slider (loads from localStorage)
    ├── admin.html                     <-- Standalone Backend Admin Panel simulator
    ├── css/
    │   ├── deepmind-slider.css
    │   └── admin-slider.css
    └── js/
        └── deepmind-slider.js
```

---

## 🧪 Testing the Standalone Demo (No WordPress required)

You can immediately test both the frontend and the backend directly in your browser:

1. Open `demo/admin.html` in your browser to test the **Backend Admin Panel**:
   - Toggle slides ON / OFF
   - Switch layouts between Hero (Top-Center) and Card (Top-Left)
   - Edit headlines, descriptions, button labels, and URLs
   - Toggle buttons ON / OFF
   - Switch between video and image backgrounds
   - Add new slides, duplicate slides, or delete slides
   - Changes are automatically saved to `localStorage`!
2. Click **View Live Slider** (or open `demo/index.html`) to see your configured slides live in the frontend slider with autoplay, gestures, and responsive peeking.

---

## 💾 Slide & Media Export & Import

DeepMind Hero Slider includes a complete, portable backup and migration system:

### 1. Exporting Slides
Navigate to **DeepMind Slider** → **Import & Export** (or click **Export (.zip)** in the top action bar on the Manage Slides screen):
- **Download Complete Package (.zip with Media)**: Bundles all slide parameters, custom texts, headline tags, descriptions, layout modes, CTA buttons, active statuses, and global slider configurations into `slides.json`, **plus** all uploaded background videos (MP4, WebM), fallback poster frames, and image assets into a bundled `media/` directory.
- **Export Config Only (slides.json)**: Downloads a lightweight JSON backup containing all slide configurations and settings.
- **Automatic Media Deduplication**: Files referenced across multiple slides are deduplicated so the export package stays compact.

### 2. Importing Slides
Navigate to **DeepMind Slider** → **Import & Export**:
1. Drag and drop or browse for your exported `.zip` or `.json` file.
2. Choose your **Import Mode**:
   - **Replace existing slides (Recommended)**: Clears current slides and installs the imported set.
   - **Append to existing slides**: Keeps current slides and adds imported slides to the end of your list with newly assigned IDs.
3. Check **Import Global Slider Settings** (optional) to import autoplay interval (e.g. 6s), advance on video end, loop mode, and dimensions.
4. Click **Upload and Import Package**.
5. **Automatic WordPress Media Library Sideloading**:
   - All bundled media files inside `media/` are automatically imported into your WordPress Media Library via `wp_upload_bits()`, `wp_insert_attachment()`, and `wp_generate_attachment_metadata()`.
   - All slide video, poster, and image URLs are automatically rewritten to match the newly registered WordPress attachment URLs.

---

## 🚀 WordPress Installation Guide

### Method 1: Upload via WordPress Admin (Recommended)

1. Log into your WordPress Admin (`/wp-admin`).
2. Navigate to **Plugins** → **Add New Plugin**.
3. Click the **Upload Plugin** button at the top.
4. Choose `lumos-deepmind-slider.zip`.
5. Click **Install Now** and then **Activate Plugin**.
6. The slider is automatically activated with the 4 default DeepMind Gemini Robotics slides loaded and ready!

### Method 2: Manual Folder Upload

1. Copy the `lumos-deepmind-slider` folder to your WordPress installation's `wp-content/plugins/` directory.
2. In WordPress Admin, go to **Plugins** and click **Activate** under **DeepMind Slider**.

---

## 📝 Shortcode & Options

Embed the slider on any page, post, or widget:

```text
[deepmind_slider]
```

### Supported Shortcode Parameters:

| Attribute | Default | Description |
| :--- | :--- | :--- |
| `caption` | `"Explore the latest"` | Bottom left caption text next to navigation controls. |
| `autoplay` | `"false"` | Auto advance slides (`"true"` or `"false"`). |
| `autoplay_speed` | `6` (or `6000`) | Auto-slide interval in seconds (e.g. `6`) or milliseconds (`6000`). |
| `advance_on_video_end` | `"true"` | Auto-advance to next slide when background video finishes playing. |
| `loop` | `"true"` | Infinite looping carousel (`"true"` or `"false"`). |
| `height` | `clamp(520px, 82svh, 800px)` | Custom CSS height (e.g. `700px` or `85vh`). |
| `theme` | `"dark"` | Slider color scheme (`"dark"` or `"light"`). |

### Example with Custom Options:

```text
[deepmind_slider caption="Featured AI Models" autoplay="true" autoplay_speed="6"]
```

### PHP Template Tag (for Theme Developers):

```php
<?php
if ( function_exists( 'lumos_deepmind_slider' ) ) {
    lumos_deepmind_slider( array(
        'caption'  => 'Latest Innovations',
        'autoplay' => true,
    ) );
}
?>
```
