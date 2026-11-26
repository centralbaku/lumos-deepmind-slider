/**
 * Gutenberg Block: DeepMind Hero Slider
 */

/* global wp */
(function (wp) {
  'use strict';

  if (!wp || !wp.blocks || !wp.element) return;

  const { registerBlockType } = wp.blocks;
  const { createElement: el } = wp.element;
  const { InspectorControls } = wp.blockEditor || wp.editor;
  const { PanelBody, TextControl, ToggleControl, RangeControl } = wp.components;

  registerBlockType('lumos/deepmind-slider', {
    title: 'DeepMind Hero Slider',
    icon: 'slides',
    category: 'media',
    description: 'Google DeepMind Gemini Robotics style responsive hero carousel with video and image backgrounds.',
    keywords: ['slider', 'carousel', 'deepmind', 'gemini', 'video'],

    edit: function (props) {
      const { attributes, setAttributes } = props;
      const { caption, autoplay, autoplay_speed, loop } = attributes;

      return el(
        'div',
        { className: 'lumos-gutenberg-block-preview', style: { padding: '24px', background: '#0e1017', color: '#fff', borderRadius: '16px', border: '1px solid #2d3139', textAlign: 'center' } },
        el(
          InspectorControls,
          {},
          el(
            PanelBody,
            { title: 'Slider Settings', initialOpen: true },
            el(TextControl, {
              label: 'Bottom Caption Text',
              value: caption,
              onChange: function (val) { setAttributes({ caption: val }); }
            }),
            el(ToggleControl, {
              label: 'Autoplay Slideshow',
              checked: autoplay,
              onChange: function (val) { setAttributes({ autoplay: val }); }
            }),
            autoplay && el(RangeControl, {
              label: 'Autoplay Speed (ms)',
              value: autoplay_speed,
              onChange: function (val) { setAttributes({ autoplay_speed: val }); },
              min: 2000,
              max: 15000,
              step: 500
            }),
            el(ToggleControl, {
              label: 'Infinite Loop Mode',
              checked: loop,
              onChange: function (val) { setAttributes({ loop: val }); }
            })
          )
        ),
        el('div', { style: { fontSize: '2rem', marginBottom: '8px' } }, '🎬'),
        el('h3', { style: { margin: '0 0 8px', color: '#fff' } }, 'DeepMind Hero Slider'),
        el('p', { style: { margin: '0 0 12px', color: '#9aa0a6', fontSize: '14px' } }, 'Displaying carousel with configured slides, video/image backgrounds, and pill navigation.'),
        el('div', { style: { display: 'inline-block', background: 'rgba(255,255,255,0.1)', padding: '6px 14px', borderRadius: '9999px', fontSize: '13px' } },
          'Caption: "' + (caption || 'None') + '" | Loop: ' + (loop ? 'Yes' : 'No') + ' | Autoplay: ' + (autoplay ? 'Yes' : 'No')
        )
      );
    },

    save: function () {
      // Dynamic server-side render
      return null;
    }
  });
})(window.wp);
