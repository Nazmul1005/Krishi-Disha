<?php
/** Locally stored, individually credited reference photographs. */
function referencePhotos(): array {
    static $photos = null;
    if ($photos !== null) return $photos;
    $manifest = __DIR__ . '/../assets/images/reference/catalog.json';
    $items = json_decode((string) @file_get_contents($manifest), true);
    $photos = [];
    if (!is_array($items)) return $photos;
    foreach ($items as $item) {
        if (!is_array($item) || !in_array($item['type'] ?? '', ['crop', 'disease'], true)) continue;
        $path = $item['image'] ?? '';
        if (!is_string($path) || !preg_match('~^assets/images/reference/(crop|disease)/([a-z_]+)\.(?:jpg|png)$~', $path, $match)) continue;
        if ($match[1] !== $item['type'] || $match[2] !== ($item['slug'] ?? '')) continue;
        if (!is_file(__DIR__ . '/../' . $path)) continue;
        foreach (['name', 'caption', 'credit', 'license'] as $field) {
            if (!is_string($item[$field] ?? null) || trim($item[$field]) === '') continue 2;
        }
        if (!filter_var($item['source'] ?? '', FILTER_VALIDATE_URL) || !str_starts_with($item['source'], 'https://commons.wikimedia.org/wiki/File:')) continue;
        if (!filter_var($item['license_url'] ?? '', FILTER_VALIDATE_URL) || !str_starts_with($item['license_url'], 'https://')) continue;
        $optimized = 'assets/images/reference/' . $item['type'] . '/' . $item['slug'] . '.webp';
        if (($item['optimized_image'] ?? '') === $optimized && is_file(__DIR__ . '/../' . $optimized)) {
            $size = @getimagesize(__DIR__ . '/../' . $optimized);
            if (is_array($size) && ($size['mime'] ?? '') === 'image/webp') {
                $item['original_image'] = $path;
                $item['image'] = $optimized;
                $item['width'] = $size[0];
                $item['height'] = $size[1];
            }
        }
        $photos[] = $item;
    }
    return $photos;
}

function referencePhoto(string $type, string $name): ?array {
    foreach (referencePhotos() as $photo) {
        if ($photo['type'] === $type && strcasecmp($photo['name'], $name) === 0) return $photo;
    }
    return null;
}
