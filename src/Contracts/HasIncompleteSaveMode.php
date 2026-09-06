<?php

namespace Blemli\SoftRequired\Contracts;

interface HasIncompleteSaveMode
{
    /**
     * Per-page override of the incomplete-save behavior.
     *
     * @return 'notify'|'confirm'|'none'
     */
    public function incompleteSaveMode(): string;
}
