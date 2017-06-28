<?php

namespace DreamFactory\Core\File\Components;

use League\Flysystem\Sftp\SftpAdapter;

class DFSftpAdapter extends SftpAdapter
{
    /** @var bool  */
    protected $recurseManually = false;

    /**
     * @param bool $recurseManually
     */
    public function setRecurseManually($recurseManually)
    {
        $this->recurseManually = $recurseManually;
    }
}