<?php

namespace DreamFactory\Core\File\Components;

use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;

class DfFtpAdapter extends FtpAdapter
{

    public function __construct(array $config)
    {
        parent::__construct(
            $this::buildFtpConnectionOptions($config)
        );
    }

    private static function buildFtpConnectionOptions(array $config): FtpConnectionOptions
    {
        $options = array_merge(
            [
                'recurseManually' => true,
                'timestampsOnUnixListingsEnabled' => true
            ],
            $config
        );
        return FtpConnectionOptions::fromArray($options);
    }

}