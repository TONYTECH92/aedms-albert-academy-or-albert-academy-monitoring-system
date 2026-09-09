<?php
/**
 * Shared helper functions
 */

function h(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function current_academic_year(): string {
    $year = (int) date('Y');
    $month = (int) date('n');
    // school year starts September
    return $month >= 9 ? "{$year}/" . ($year + 1) : ($year - 1) . "/{$year}";
}

/** Compute a simple grade letter from a total score out of 100 */
function score_to_grade(float $score): string {
    if ($score >= 80) return 'A';
    if ($score >= 70) return 'B';
    if ($score >= 60) return 'C';
    if ($score >= 50) return 'D';
    if ($score >= 40) return 'E';
    return 'F';
}

/** Flash message helpers (stored in session, shown once) */
function flash_set(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}
