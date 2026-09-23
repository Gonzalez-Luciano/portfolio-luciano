# Scroll video — generation record

Source record for `ink-tree-network-v1.mp4`, kept in this folder together with `-v2`, `-v3` and their posters; superseded by `-v4` (see [V4](#v4) below), which is the only encode served from `web/public/media/scroll/` (§11 of the design spec). The two frames in this folder are generation inputs only; none of the files here are served by the site.

| Item | Value |
|---|---|
| Frames | `frame-start.png`, `frame-end.png` (1672×941, generated with ChatGPT image generation) |
| Video model | Wan 2.2 First-Last Frame (Apache 2.0), public Hugging Face demo |
| Settings | Duration 5.1 s (81 frames at 16 fps), inference steps 8, guidance scale high/low noise 1, randomized seed |
| Seed | Kept by Luciano; add it here when regenerating |
| Output | 848×480, 81 frames, 5.06 s, no audio, 246 KB. Served as `ink-tree-network-v2.mp4`, an all-intra re-encode of this same-frame source (H.264, `-g 1`, CRF 20, `+faststart`, 868 KB) so the `currentTime` fallback seeks to any frame; poster `ink-tree-network-v2-poster.webp` is frame 0 (3 KB) |
| Upscale | The 81 v1 frames were upscaled 4× with Real-ESRGAN (`x4plus-anime` model) to 3392×1920 PNGs, then downscaled 2× (Lanczos) to 1696×960 — exactly double the original resolution — and re-encoded all-intra: H.264, `libx264`, `-g 1`, no B-frames, `yuv420p`, CRF 20, `+faststart`, no audio, 81 frames at 16 fps. Served as `ink-tree-network-v3.mp4` (2.16 MB); poster `ink-tree-network-v3-poster.webp` is frame 0 at the same resolution (7 KB) |

## Video prompt

```
A single continuous terracotta ink line slowly draws itself on warm cream paper. It starts as a small sprouting root near the bottom center-right and grows upward into a branching tree. As the branches spread, each branch tip turns into a small circular node, and thin dotted lines connect the nodes into a calm network, like a map of connected services. While the drawing grows, the paper gradually darkens from soft daylight cream to a deep espresso-brown night, and the nodes begin to glow a warm orange. Minimal hand-drawn line art, flat 2D illustration, subtle paper grain, consistent line weight, smooth progressive drawing, very slow and steady camera push-in, one continuous shot. The left third of the frame stays mostly empty paper.
```

## Negative prompt

With guidance scale 1 the demo ignores it; it is kept for reruns with guidance above 1.

```
text, letters, numbers, words, watermark, logo, signature, people, faces, hands, photorealistic, 3D render, glossy, camera shake, cuts, scene change, flicker, strobing, color flashes, busy background, extra objects, blur
```

## First frame prompt

```
Create a wide 16:9 landscape illustration.

Minimal hand-drawn ink illustration on warm cream paper (background color #F5EFE6) with a subtle, even paper grain. The canvas is almost empty and calm.

Near the bottom, slightly to the right of center, a single thin terracotta ink line (#9A4E2A) forms a tiny sprouting root with one short, delicate stem starting to grow upward. Only a few strokes in total. A faint, very light horizon line crosses the bottom of the page.

Keep the left third and the whole upper half as clean, empty paper so text can be placed on top later.

Style: flat 2D line art, consistent fine line weight, elegant and restrained, like a pen sketch in a designer's notebook. Soft daylight, no shadows.

Do not include: any text, letters, numbers, logos, watermark, signature, people, hands, frames or borders, photorealism, 3D, gradients or extra objects.
```

## Last frame prompt (same chat as the first frame)

```
Using the previous image as the exact starting point, create its final state as a wide 16:9 landscape illustration with the same camera framing, the same paper grain and the same hand-drawn ink style.

The tiny root has now grown into a complete tree drawn with the same thin terracotta ink line, rising from the same spot near the bottom, slightly right of center, with the crown spreading up and to the right. Each branch tip ends in a small circular node. The nodes are connected by thin dotted lines in a light beige (#C7B3A1), forming a calm, readable network — like a map of connected services — not a dense web. Between 12 and 18 nodes.

It is now night: the whole paper is a deep espresso brown (#1A1411) with the same subtle grain. The ink lines have turned a warm glowing orange (#E08E5E), and each node has a soft warm glow, no harsh bloom.

Keep the left third mostly empty dark paper so light text can be placed on top later.

Style: flat 2D line art, consistent fine line weight, elegant and restrained.

Do not include: any text, letters, numbers, logos, watermark, signature, people, hands, frames or borders, photorealism, 3D or extra objects.
```

## Measured facts used by the design

- Brightness stays light until frame ≈40, then decreases continuously to the final night; no brightness increases.
- Transition frames ≈44–60 have very low contrast (std 1.7–2.9 vs 13.8 at the end): corrected at runtime.
- Tree node color measured on the last frame: core `#E38B50`, halo `#7E4F31`.
- Tree base at the same position in both frames (x ≈ 1060 px of 1672).
- Text legibility measured per frame (text zone: left 5–40 % × middle 50 %; nav zone: top 10 %), with the runtime correction applied:
  - dark text `#2D251B` passes 4.5:1 up to frame 56 (p 0.70);
  - light text `#F3E9DD` passes 4.5:1 from frame 62 (p 0.775);
  - between p 0.70 and 0.78 neither passes on its own, hence the dark veil described in the design spec (section 7.3).

## V4

A new video, not a re-encode of v1: supplied by Luciano on 2026-09-22 as `hf_20260922_234439_4c31be34-18d8-4d5e-8155-c27800162931.mp4`. Its generator, prompts and seed are not recorded here. It was converted only; no frame was edited, cropped, colour-corrected or regenerated.

| Item | Value |
|---|---|
| Source | HEVC Main 10 (`yuv420p10le`), 1920×1080 (16:9), BT.709 limited range, progressive, 24 fps, 121 frames, 5.04 s, AAC audio track, 3,949,701 bytes, SHA-256 `c414cbee91c09a87fc5e25ee67224766a0076a99349171172b03d9c258143cff` |
| Served video | `ink-tree-network-v4.mp4`: H.264 High, `libx264 -preset slow`, all-intra (`-g 1 -keyint_min 1`, every frame a key frame), no B-frames, 8-bit `yuv420p`, BT.709 tags, CRF 23, `+faststart`, audio dropped (`-an`), 1920×1080, 121 frames at 24 fps, 2,328,248 bytes. CRF 23 instead of 20 keeps it inside the 3 MB budget (CRF 20 gives 3.69 MB) at an imperceptible cost: PSNR against the source 49.7 dB average (48.0 min), vs 50.9 dB at CRF 20 |
| Poster | `ink-tree-network-v4-poster.webp`: frame 0 (pts 0, I-frame) of the source, `select=eq(n\,0)`, `libwebp -quality 82`, 1920×1080, 14,012 bytes; PSNR 43.3 dB / SSIM 0.994 against a lossless PNG of the same frame |

```sh
ffmpeg -i <source> -map 0:v:0 -an -vf format=yuv420p -c:v libx264 -preset slow -crf 23 -g 1 -keyint_min 1 -bf 0 \
  -pix_fmt yuv420p -color_primaries bt709 -color_trc bt709 -colorspace bt709 -color_range tv -movflags +faststart \
  ink-tree-network-v4.mp4
ffmpeg -i <source> -map 0:v:0 -vf "select=eq(n\,0)" -frames:v 1 -fps_mode passthrough -c:v libwebp -quality 82 \
  ink-tree-network-v4-poster.webp
```

### V4 measured facts

Same method as above (text zone: left 5–40 % × middle 50 %; nav zone: top 10 %; 1st/99th luminance percentile of the background). The method reproduces the v3 figure of frame 56 for dark text.

- Night falls much earlier than in v3: mean luminance 0.88 up to frame 10, 0.53 at frame 25, 0.13 at frame 45, 0.025 from frame 65.
- Contrast stays high through the transition (std ≥ 7.4 vs 1.7 in v3), so the v3 contrast/saturation correction filter was removed.
- Dark text `#2D251B` passes 4.5:1 up to frame 35; the beat 2 accent `#9A4E2A` (large text) passes 3:1 up to frame 26; light text `#F3E9DD` passes 4.5:1 from frame 45, or from frame 32 over the 45 % veil.
- Played linearly, the video would put beat 2 and the dark nav on night frames. `videoProgress(p)` in `web/src/lib/scene-timeline.ts` maps scroll to video time instead: p 0 → 0.63 onto frames 0–25, 0.63 → 0.70 onto 25–35, 0.70 → 0.78 onto 35–45 (behind the veils) and 0.78 → 1 onto 45–120. The beats, colours, veils, `NAV_LIGHT_THRESHOLD` and the backdrop timings are unchanged.
- Measured over the whole track with that mapping and the veils (worst of the two blended frames): nav dark ≥ 4.60:1, nav light ≥ 5.64:1, beat 1 ≥ 9.88:1, beat 2 ink ≥ 8.61:1 / accent ≥ 3.43:1 / muted ≥ 4.20:1, beat 3 light ≥ 6.81:1 / glow ≥ 3.19:1. No failures.
