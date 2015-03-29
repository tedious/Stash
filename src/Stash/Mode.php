<?php namespace Stash;

class Mode
{
    /**
     * Normal Mode
     *
     * Default value
     */
    const NORMAL = 0;

    /**
     * Read only mode
     *
     * Set() has no effect
     */
    const READ_ONLY = 1;

    /**
     * Write only mode
     *
     * Get() has no effect
     */
    const WRITE_ONLY = 2;

    /**
     * Disabled mode
     *
     * Get() and Set() has no effect.
     *
     * Get() will return null
     */
    const DISABLED = 3;

    /**
     * Forced miss mode
     *
     * Get() and Set() work normally.
     *
     * isMiss() will always return true
     */
    const FORCE_MISS = 4;
}