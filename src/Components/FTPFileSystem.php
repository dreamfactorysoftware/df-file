<?php

namespace DreamFactory\Core\File\Components;

class FTPFileSystem extends BaseFlysystem
{
    /**
     * {@inheritdoc}
     */
    protected function setAdapter($config)
    {
        $this->adapter = new DfFtpAdapter($config);
    }
}