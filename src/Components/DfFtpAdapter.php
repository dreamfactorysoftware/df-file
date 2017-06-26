<?php

namespace DreamFactory\Core\File\Components;

use League\Flysystem\Adapter\Ftp;

class DfFtpAdapter extends Ftp
{
    /**
     * @inheritdoc
     *
     * @param string $directory
     */
    protected function listDirectoryContentsRecursive($directory)
    {
        $listing = $this->normalizeListing($this->ftpRawlist('-aln', $directory) ?: [], $directory);
        $output = [];

        foreach ($listing as $directory) {
            $output[] = $directory;
            if ($directory['type'] !== 'dir') {
                continue;
            }

            $output = array_merge($output, $this->listDirectoryContentsRecursive($directory['path']));
        }

        return $output;
    }

    /**
     * @param string $path
     *
     * @return array|bool
     */
    public function getMetadata($path)
    {
        $connection = $this->getConnection();

        if ($path === '') {
            return ['type' => 'dir', 'path' => ''];
        }

        if (@ftp_chdir($connection, $path) === true) {
            $this->setConnectionRoot();

            return ['type' => 'dir', 'path' => $path];
        }

        $listing = $this->ftpRawlist('-A', str_replace('*', '\\*', $path));

        if (empty($listing) || in_array('total 0', $listing, true)) {
            return false;
        }

        if (preg_match('/.* not found/', $listing[0])) {
            return false;
        }

        if (preg_match('/^total [0-9]*$/', $listing[0])) {
            array_shift($listing);
        }

        return $this->normalizeObject($listing[0], $path);
    }
}