# Phase 2 prototype asset manifest

## Source and derivation contract

- Approved source: `docs/content/approved-assets/professional-photo.jpg` (`SRC-010`).
- Source dimensions and format: 1200 × 1600 JPEG.
- Source SHA-256: `05ce50ba6cdb7b4fbc4682aa5b41075e1d445f4bc509121852a97884d5be29d5`.
- Builder: `docs/prototypes/phase-2/tools/build-assets.py`, using Pillow `ImageOps.fit`, RGB conversion, Lanczos resampling, WebP `quality=82`, and `method=6`.
- Final portrait centering: `(0.5, 0.42)`; final wide centering: `(0.5, 0.34)`.
- The derivation creates no new generated visual content and does not alter the approved source.
- Both derivatives were checked after generation: no EXIF metadata remains. The orange background is preserved, the subject is not stretched, and the face remains recognizable; the portrait retains the complete head-and-shoulders composition. The wider responsive crop prioritizes the approved face composition within its required 4:3 ratio.

## Accessibility and confidentiality review

- Spanish alt text: `Retrato profesional de Luciano González sobre fondo naranja`.
- English alt text: `Professional portrait of Luciano González against an orange background`.
- Review result: the two derivatives originate only from approved public asset `SRC-010`; no new claims, identifiers, confidential employer/client details, or generated imagery were introduced. EXIF metadata was removed. They remain prototype-local assets and are not a public CMS/media publication action.
- Integration follow-up: verify crop harmony and surrounding UI contrast in both themes before future publication, as required by `docs/content/ASSET_INVENTORY.md`.

## Pinned upstream font sources

| Asset | Exact source | Commit | License file |
| --- | --- | --- | --- |
| Instrument Sans variable | `https://raw.githubusercontent.com/Instrument/instrument-sans/7fa22308a3d0c94ee2b3cd537a1196b65db34a3e/fonts/webfonts/InstrumentSans%5Bwdth,wght%5D.woff2` | `7fa22308a3d0c94ee2b3cd537a1196b65db34a3e` | `fonts/InstrumentSans-OFL.txt` (SIL Open Font License) |
| IBM Plex Mono Regular Latin-1 | `https://raw.githubusercontent.com/IBM/plex/bf260093582f04622aacc1e9f9ca604d7ccd0c42/packages/plex-mono/fonts/split/woff2/IBMPlexMono-Regular-Latin1.woff2` | `bf260093582f04622aacc1e9f9ca604d7ccd0c42` | `fonts/IBMPlexMono-OFL.txt` (SIL Open Font License) |
| IBM Plex Mono SemiBold Latin-1 | `https://raw.githubusercontent.com/IBM/plex/bf260093582f04622aacc1e9f9ca604d7ccd0c42/packages/plex-mono/fonts/split/woff2/IBMPlexMono-SemiBold-Latin1.woff2` | `bf260093582f04622aacc1e9f9ca604d7ccd0c42` | `fonts/IBMPlexMono-OFL.txt` (SIL Open Font License) |

## Files

| File | Dimensions / format | Bytes | SHA-256 |
| --- | --- | ---: | --- |
| `fonts/InstrumentSans-Variable.woff2` | WOFF2 variable | 88,784 | `aa72922aafcc0dc18f36ec1d805b0212057dabe8b9d5b8b57f67035aea1b826d` |
| `fonts/IBMPlexMono-Regular-Latin1.woff2` | WOFF2 | 17,544 | `e8993d946649b9d01abb1ed06d574b19d8ea3e66b5c3948602db335c44c18e56` |
| `fonts/IBMPlexMono-SemiBold-Latin1.woff2` | WOFF2 | 17,872 | `b7acd05041ab65f3b7039e218ddd893065e11a07e85ea85019473152a51b6b7d` |
| `fonts/InstrumentSans-OFL.txt` | SIL Open Font License text | 4,403 | `9e27a72ed30eb49a08678f6a5d6ed98ec7ba5368f541637ee0683ec9134ef966` |
| `fonts/IBMPlexMono-OFL.txt` | SIL Open Font License text | 4,456 | `7e6b2818edbd8f6a01ae80641cc8f16a51080d08fb4e532be3a0b6f74adb07da` |
| `images/profile-portrait.webp` | 900 × 1200 WEBP; EXIF empty | 77,638 | `3999cd8675b6923cf4a5c63455791b7cceb1faa237ac596d5e37afd640492489` |
| `images/profile-wide.webp` | 1200 × 900 WEBP; EXIF empty | 50,276 | `5bc1dde5ba9b3e4ce085b43a134724ed5cb3ff5a15813c860a2e9c3b8604fd87` |
