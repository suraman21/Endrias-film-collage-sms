<?php
/**
 * Staff photo uploads for the HR employee domain (sql/099).
 *
 * Modelled on MemberFileService hardening (same rules, separate
 * directory — the HR domain never shares the members upload tree):
 *   • verified MIME only (JPG/PNG/GIF/WebP/BMP), max 5 MB
 *   • GD re-encode to strip EXIF and any appended payload
 *     (polyglot protection); falls back to the original file when
 *     GD is unavailable
 *   • random 32-hex filename — no user-controlled names
 *   • 0755 directories, 0644 files — never world-writable
 *
 * Stored paths are 'uploads/staff/photos/{hash}.{ext}' (web-relative
 * from /admin/), mirroring the member photo convention.
 */
namespace App\Services;

final class StaffFileService
{
    public const MAX_BYTES = 5242880; // 5 MB

    private const IMAGE_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
        'image/bmp'  => 'bmp',
    ];

    /** Field name => storage category. */
    private const FIELDS = [
        'staff_photo' => ['category' => 'photos', 'images' => true],
    ];

    /**
     * Store an image uploaded as $_FILES[$field] for the staff domain.
     *
     * @return array{path: ?string, error: ?string}
     */
    public static function storeRequestUpload(string $field): array
    {
        if (!isset(self::FIELDS[$field])) {
            return ['path' => null, 'error' => 'Unsupported upload field.'];
        }
        if (!isset($_FILES[$field]) || (int)($_FILES[$field]['error'] ?? \UPLOAD_ERR_NO_FILE) === \UPLOAD_ERR_NO_FILE) {
            return ['path' => null, 'error' => null];
        }

        $file = $_FILES[$field];
        $error = (int)($file['error'] ?? \UPLOAD_ERR_NO_FILE);
        if ($error !== \UPLOAD_ERR_OK) {
            return ['path' => null, 'error' => self::uploadError($error)];
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['path' => null, 'error' => 'The uploaded file could not be verified.'];
        }
        $size = @filesize($tmp);
        if ($size === false || $size <= 0 || $size > self::MAX_BYTES) {
            return ['path' => null, 'error' => 'File must be between 1 byte and 5 MB.'];
        }

        $mime = self::detectMime($tmp);
        $extension = self::IMAGE_MIMES[$mime] ?? null;
        $reencodedTmp = null;
        if ($extension !== null) {
            if (@getimagesize($tmp) === false) {
                return ['path' => null, 'error' => 'The file is not a valid image.'];
            }
            $reencodedTmp = self::reencodeImage($tmp, $mime, $extension);
            if ($reencodedTmp !== null) {
                $tmp = $reencodedTmp;
            }
        } else {
            return ['path' => null, 'error' => 'Only verified JPG, PNG, GIF, WebP, or BMP images are allowed.'];
        }

        try {
            $dir = self::root() . '/' . self::FIELDS[$field]['category'];
            if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
                return ['path' => null, 'error' => 'Upload storage is unavailable.'];
            }

            $name = bin2hex(random_bytes(16)) . '.' . $extension;
            $target = $dir . '/' . $name;

            $saveFile = static function (string $source, string $t): bool {
                $bytes = @file_get_contents($source);
                if ($bytes === false) {
                    return false;
                }
                $ok = @file_put_contents($t, $bytes, \LOCK_EX);
                return $ok !== false;
            };

            if (!$saveFile($tmp, $target)) {
                return ['path' => null, 'error' => 'The upload could not be stored.'];
            }
            @chmod($target, 0644);

            return ['path' => 'uploads/staff/' . self::FIELDS[$field]['category'] . '/' . $name, 'error' => null];
        } finally {
            if ($reencodedTmp !== null && is_file($reencodedTmp)) {
                @unlink($reencodedTmp);
            }
        }
    }

    /** Delete a stored file (stored path as returned above). Best effort. */
    public static function discard(?string $storedPath): void
    {
        if ($storedPath === null) {
            return;
        }
        if (!preg_match('#^uploads/staff/(photos)/([a-f0-9]{32}\.(?:jpg|png|gif|webp|bmp))$#D', (string)$storedPath, $m)) {
            return; // never touch anything outside the staff photos dir
        }
        $file = self::root() . '/' . $m[1] . '/' . $m[2];
        if (is_file($file)) {
            @unlink($file);
        }
    }

    private static function root(): string
    {
        $base = defined('UPLOADS_PATH') ? UPLOADS_PATH : dirname(__DIR__, 2) . '/uploads';
        return $base . '/staff';
    }

    private static function detectMime(string $path): string
    {
        $finfo = @finfo_open(\FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $mime = finfo_file($finfo, $path);
            finfo_close($finfo);
            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        }
        return 'application/octet-stream';
    }

    private static function reencodeImage(string $source, string $mime, string $extension): ?string
    {
        if (!function_exists('imagecreatetruecolor') || !function_exists('imagecopyresampled')) {
            return null; // GD unavailable — keep the verified original
        }
        $image = null;
        switch ($mime) {
            case 'image/jpeg': $image = @imagecreatefromjpeg($source); break;
            case 'image/png':  $image = @imagecreatefrompng($source); break;
            case 'image/gif':  $image = @imagecreatefromgif($source); break;
            case 'image/webp': $image = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : null; break;
            case 'image/bmp':
            case 'image/x-ms-bmp': $image = function_exists('imagecreatefrombmp') ? @imagecreatefrombmp($source) : null; break;
        }
        if (!($image instanceof \GdImage)) {
            return null;
        }
        $tmp = tempnam(sys_get_temp_dir(), 'staff-img-');
        if ($tmp === false) {
            imagedestroy($image);
            return null;
        }
        $out = false;
        switch ($extension) {
            case 'jpg':  $out = @imagejpeg($image, $tmp, 90); break;
            case 'png':  $out = @imagepng($image, $tmp, 6); break;
            case 'gif':  $out = @imagegif($image, $tmp); break;
            case 'webp': $out = function_exists('imagewebp') ? @imagewebp($image, $tmp, 90) : false; break;
            case 'bmp':  $out = function_exists('imagebmp') ? @imagebmp($image, $tmp) : false; break;
        }
        imagedestroy($image);
        if (!$out || !is_file($tmp) || filesize($tmp) <= 0) {
            @unlink($tmp);
            return null;
        }
        // Caller owns the temp file from here (storeRequestUpload
        // removes it in its finally block).
        return $tmp;
    }

    private static function uploadError(int $error): string
    {
        return match ($error) {
            \UPLOAD_ERR_INI_SIZE, \UPLOAD_ERR_FORM_SIZE => 'The file is too large (max 5 MB).',
            \UPLOAD_ERR_PARTIAL   => 'The upload was incomplete. Please try again.',
            \UPLOAD_ERR_NO_FILE   => 'No file was uploaded.',
            \UPLOAD_ERR_NO_TMP_DIR => 'The upload failed (missing temporary storage).',
            \UPLOAD_ERR_CANT_WRITE => 'The upload failed (could not write the file).',
            default => 'The upload failed. Please try again.',
        };
    }
}
