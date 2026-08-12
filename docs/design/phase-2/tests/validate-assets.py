from __future__ import annotations

import hashlib
from pathlib import Path

from PIL import Image


ROOT = Path(__file__).resolve().parents[4]
ASSETS = ROOT / "docs/prototypes/phase-2/assets"
IMAGES = ASSETS / "images"
FONTS = ASSETS / "fonts"
MANIFEST = ASSETS / "ASSET_MANIFEST.md"

EXPECTED_IMAGES = {
    "profile-portrait.webp": ((900, 1200), 180_000),
    "profile-wide.webp": ((1200, 900), 180_000),
}
EXPECTED_FONTS = (
    "InstrumentSans-Variable.woff2",
    "IBMPlexMono-Regular-Latin1.woff2",
    "IBMPlexMono-SemiBold-Latin1.woff2",
    "InstrumentSans-OFL.txt",
    "IBMPlexMono-OFL.txt",
)


def sha256(path: Path) -> str:
    payload = path.read_bytes()
    if path.suffix == ".txt":
        payload = payload.replace(b"\r\n", b"\n")
    return hashlib.sha256(payload).hexdigest()


def main() -> None:
    for name, (dimensions, max_bytes) in EXPECTED_IMAGES.items():
        path = IMAGES / name
        assert path.is_file(), f"Missing image asset: {path.relative_to(ROOT)}"
        assert path.stat().st_size < max_bytes, f"Image exceeds {max_bytes} bytes: {name}"
        with Image.open(path) as image:
            assert image.format == "WEBP", f"Image is not WEBP: {name}"
            assert image.size == dimensions, f"Unexpected dimensions for {name}: {image.size}"
            assert not image.getexif(), f"EXIF metadata remains in {name}"
            if name == "profile-wide.webp":
                red, green, blue = image.convert("RGB").getpixel((600, 0))
                assert (
                    red >= 200 and red > green + 50 and green > blue + 20
                ), "Wide asset must preserve an orange background above the uncropped portrait"
    for name in EXPECTED_FONTS:
        path = FONTS / name
        assert path.is_file(), f"Missing font or license asset: {path.relative_to(ROOT)}"
        assert path.stat().st_size > 0, f"Empty font or license asset: {name}"
    assert MANIFEST.is_file(), f"Missing asset manifest: {MANIFEST.relative_to(ROOT)}"
    manifest = MANIFEST.read_text(encoding="utf-8")
    for name in (*EXPECTED_IMAGES, *EXPECTED_FONTS):
        path = (IMAGES if name in EXPECTED_IMAGES else FONTS) / name
        assert sha256(path) in manifest, f"Manifest missing SHA-256 for {name}"

    print("Phase 2 assets pass.")


if __name__ == "__main__":
    main()
