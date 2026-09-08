/**
 * Client-side pre-check for a picked/dropped file, mirroring the corresponding
 * Form Request's `mimes`/`max` rules so the user gets instant feedback instead of
 * a round trip. The backend rule is always the actual source of truth.
 *
 * @param {File} file
 * @param {{ extensions?: string[], maxSizeBytes?: number }} constraints
 * @returns {string|null} an error message, or null if the file is acceptable
 */
export function validateFile(file, { extensions, maxSizeBytes } = {}) {
    if (!file) {
        return null;
    }

    if (extensions && extensions.length > 0) {
        const name = file.name.toLowerCase();
        const matches = extensions.some((extension) => name.endsWith(extension.toLowerCase()));

        if (!matches) {
            return `The file must be one of: ${extensions.join(', ')}.`;
        }
    }

    if (maxSizeBytes && file.size > maxSizeBytes) {
        const maxMb = maxSizeBytes / (1024 * 1024);

        return `The file must not be larger than ${maxMb % 1 === 0 ? maxMb : maxMb.toFixed(1)} MB.`;
    }

    return null;
}
