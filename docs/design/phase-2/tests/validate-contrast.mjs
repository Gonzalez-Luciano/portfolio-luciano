import assert from 'node:assert/strict';

const pairs = [
  ['#1B1E1C', '#EEE9DE', 4.5, 'light primary'],
  ['#565B56', '#EEE9DE', 4.5, 'light muted'],
  ['#A94322', '#EEE9DE', 4.5, 'light accent'],
  ['#817B70', '#EEE9DE', 3, 'light meaningful border'],
  ['#8B2E24', '#EEE9DE', 4.5, 'light error'],
  ['#F1ECE1', '#171918', 4.5, 'dark primary'],
  ['#B8B3A9', '#171918', 4.5, 'dark muted'],
  ['#F07A4B', '#171918', 4.5, 'dark accent'],
  ['#777A74', '#171918', 3, 'dark meaningful border'],
  ['#FF9B8A', '#171918', 4.5, 'dark error'],
];

const channel = (value) => {
  const normalized = value / 255;
  return normalized <= 0.04045
    ? normalized / 12.92
    : ((normalized + 0.055) / 1.055) ** 2.4;
};

const luminance = (hex) => {
  assert.match(hex, /^#[0-9a-f]{6}$/i, `Invalid hex color ${hex}`);
  const rgb = [1, 3, 5].map((index) => Number.parseInt(hex.slice(index, index + 2), 16));
  return 0.2126 * channel(rgb[0]) + 0.7152 * channel(rgb[1]) + 0.0722 * channel(rgb[2]);
};

const contrast = (foreground, background) => {
  const [lighter, darker] = [luminance(foreground), luminance(background)].sort((a, b) => b - a);
  return (lighter + 0.05) / (darker + 0.05);
};

for (const [foreground, background, minimum, name] of pairs) {
  const ratio = contrast(foreground, background);
  assert.ok(ratio >= minimum, `${name} contrast ${ratio.toFixed(2)}:1 is below ${minimum}:1`);
  console.log(`${name}: ${ratio.toFixed(2)}:1 (minimum ${minimum}:1)`);
}
