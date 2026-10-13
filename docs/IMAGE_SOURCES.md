# Crop and disease reference photographs

The photo library contains 34 real photographs (21 crops and 13 disease or pest examples) downloaded from Wikimedia Commons and stored under `assets/images/reference/`. Each entry in [`catalog.json`](../assets/images/reference/catalog.json) records the original file page, photographer or credited institution, license, visible caption, and SHA-256 of the downloaded image. The public photo library displays this credit and links to the original file and license for each image. Preserve these notices when reusing an image.

These are **illustrative reference photos**. They were photographed in different places and conditions; they are not a collection of verified Bangladesh farm observations. A similar-looking symptom can have several causes. In particular, the wheat rust photo shows **leaf rust**, while the database's broader “Wheat Rust” record discusses other rust types too, so it is not automatically attached to that record. The mango anthracnose image shows a **flower panicle**, not affected fruit. Photos do not establish a diagnosis or a treatment.

Existing uploaded crop and disease images take priority over reference photos. Reference photos are displayed on reviewed records when the name matches and no uploaded image exists. The photo library itself is available without reviewed database records; adding photos does not publish any unreviewed agronomic advice.

## Optimized derivatives

Each downloaded original remains unchanged at its catalog `image` path and retains its original `sha256`. A locally generated WebP sibling is recorded in `optimized_image`, with its own `optimized_sha256`, byte size and dimensions. The derivative keeps the complete framing, resizes the longest edge to at most 960 pixels without enlarging small sources, encodes at quality 80 and strips embedded metadata. Source links, visible credits, captions and licenses remain in the catalog and the public cards. Each derivative retains its original photograph's license, including the same ShareAlike license where applicable; optimization is the modification made by Krishi Disha.

`includes/reference_photos.php` selects a valid matching WebP derivative for display and falls back to the downloaded original if the derivative is missing or unreadable. The return value preserves `original_image` when using a derivative; the catalog's original checksum is never replaced with a derivative checksum. Uploaded images still take priority.

Generate derivatives with `node bin/images.js` (requires the open-source `cwebp` command from libwebp). Run `node bin/images.js --check` to verify every original and derivative checksum, required attribution, path, WebP file header and byte size without regenerating files. Originals and derivatives are tracked together, so production does not require an encoder.

## Locally hosted fonts

The interface uses Manrope for Latin text and Noto Sans Bengali for Bengali text. Both use SIL Open Font License 1.1 and are shipped with their complete notices under `assets/fonts/`; [the font source guide](../assets/fonts/README.md) records official source URLs, weights and local file paths. No third-party font request is needed at runtime.

Before adding more photos, verify the crop, host, affected plant part, condition and original file page; use a reusable license, download the image locally, update `catalog.json` with an accurate caption and credit, and check the displayed image visually. Where the image only depicts one example of a broader condition, say so in the caption and do not automatically map it to broader advice.
