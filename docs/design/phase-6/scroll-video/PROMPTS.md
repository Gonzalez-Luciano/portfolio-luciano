# Scroll video — generation record

Source record for `web/public/media/scroll/ink-tree-network-v1.mp4`. The two frames in this folder are generation inputs only; they are not served by the site.

| Item | Value |
|---|---|
| Frames | `frame-start.png`, `frame-end.png` (1672×941, generated with ChatGPT image generation) |
| Video model | Wan 2.2 First-Last Frame (Apache 2.0), public Hugging Face demo |
| Settings | Duration 5.1 s (81 frames at 16 fps), inference steps 8, guidance scale high/low noise 1, randomized seed |
| Seed | Kept by Luciano; add it here when regenerating |
| Output | 848×480, 81 frames, 5.06 s, no audio, 246 KB |

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
