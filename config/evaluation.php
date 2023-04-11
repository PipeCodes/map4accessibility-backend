<?php

return [
    /**
     * Alignment parameters for calculating the
     * evaluation score of a place, after an evaluation has
     * been submitted.
     */
    'alignment_w' => env('ALIGNMENT_PARAMETER_W', 0.3),
    'alignment_z' => env('ALIGNMENT_PARAMETER_Z', 0.25),
];
