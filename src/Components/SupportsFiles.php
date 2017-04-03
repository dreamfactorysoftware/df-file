<?php
namespace DreamFactory\Core\File\Components;

use DreamFactory\Core\File\Models\FilePublicPath;

trait SupportsFiles
{
    /**
     * {@inheritdoc}
     */
    public static function getConfig($id, $local_config = null, $protect = true)
    {
        $config = parent::getConfig($id, $local_config, $protect);

        /** @var FilePublicPath $pathConfig */
        if (!empty($pathConfig = FilePublicPath::find($id))) {
            $config = array_merge($config, $pathConfig->toArray());
        }

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public static function setConfig($id, $config, $local_config = null)
    {
        $result = parent::setConfig($id, $config, $local_config);

        $resultPath = FilePublicPath::setConfig($id, $config, $local_config);
        if ($resultPath) {
            $result = array_merge((array)$result, (array)$resultPath);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public static function storeConfig($id, $config)
    {
        parent::storeConfig($id, $config);

        FilePublicPath::storeConfig($id, $config);
    }

    /**
     * {@inheritdoc}
     */
    public static function getConfigSchema()
    {
        $out = (array)parent::getConfigSchema();
        $pathSchema = (array)FilePublicPath::getConfigSchema();

        return array_merge($out, $pathSchema);
    }
}