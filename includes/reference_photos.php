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
        if (!is_string($path) || !preg_match('~^assets/images/reference/(crop|disease)/[a-z_]+\.(?:jpg|png)$~', $path)) continue;
        if (!is_file(__DIR__ . '/../' . $path)) continue;
        if (!filter_var($item['source'] ?? '', FILTER_VALIDATE_URL) || !str_starts_with($item['source'], 'https://commons.wikimedia.org/wiki/File:')) continue;
        if (!filter_var($item['license_url'] ?? '', FILTER_VALIDATE_URL) || !str_starts_with($item['license_url'], 'https://')) continue;
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
