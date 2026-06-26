<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Dilempar bila transisi status ShotTask melanggar aturan workflow
 * (urutan transisi, dependensi tahap, atau hak akses).
 */
class InvalidShotTaskTransition extends RuntimeException {}
