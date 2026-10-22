<?php

namespace App\Services;

use RuntimeException;

/**
 * A failure whose message can be shown to the user as is.
 */
class GenerationFailed extends RuntimeException {}
