from pathlib import Path

from PIL import Image, ImageOps


ROOT = Path(__file__).resolve().parents[4]
SOURCE = ROOT / "docs/content/approved-assets/professional-photo.jpg"
OUTPUT = ROOT / "docs/prototypes/phase-2/assets/images"


def main() -> None:
    OUTPUT.mkdir(parents=True, exist_ok=True)
    with Image.open(SOURCE) as image:
        image = image.convert("RGB")
        portrait = ImageOps.fit(
            image, (900, 1200), Image.Resampling.LANCZOS, centering=(0.5, 0.42)
        )
        wide = Image.new("RGB", (1200, 900), image.getpixel((0, 0)))
        contained = ImageOps.contain(image, wide.size, Image.Resampling.LANCZOS)
        wide.paste(contained, ((wide.width - contained.width) // 2, 0))
        portrait.save(OUTPUT / "profile-portrait.webp", "WEBP", quality=82, method=6)
        wide.save(OUTPUT / "profile-wide.webp", "WEBP", quality=82, method=6)


if __name__ == "__main__":
    main()
