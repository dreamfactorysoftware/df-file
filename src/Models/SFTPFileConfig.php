<?php

namespace DreamFactory\Core\File\Models;

use DreamFactory\Core\File\Components\SupportsFiles;
use DreamFactory\Core\Models\BaseServiceConfigModel;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class SFTPFileConfig extends BaseServiceConfigModel
{
    use SupportsFiles;

    /** @var string */
    protected $table = 'sftp_service_config';

    /** @var array */
    protected $encrypted = ['password', 'passphrase', 'private_key'];

    /** @var array */
    protected $protected = ['password', 'passphrase', 'private_key'];

    /** @var array */
    protected $fillable = [
        'service_id',
        'host',
        'port',
        'username',
        'password',
        'timeout',
        'host_fingerprint',
        'private_key',
        'passphrase'
    ];

    /** @var array */
    protected $casts = [
        'service_id' => 'integer',
        'port'       => 'integer',
        'timeout'    => 'integer'
    ];

    /**
     * {@inheritdoc}
     */
    protected function decryptAttribute($key, &$value)
    {
        if ($key === 'private_key' && !is_null($value) && !$this->encryptedView) {
            $value = $this->decryptPrivateKey($value);

            return true;
        }

        return parent::decryptAttribute($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    protected function addDecryptedAttributesToArray(array $attributes)
    {
        if (!$this->encryptedView && !empty($attributes['private_key'])) {
            $privateKey = $this->decryptPrivateKey($attributes['private_key']);
            unset($attributes['private_key']);

            return array_merge(parent::addDecryptedAttributesToArray($attributes), ['private_key' => $privateKey]);
        }

        return parent::addDecryptedAttributesToArray($attributes);
    }

    private function decryptPrivateKey($value)
    {
        try {
            return Crypt::decrypt($value);
        } catch (DecryptException $e) {
            // Older configurations stored a plaintext key or local file path.
            return $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getConfigSchema()
    {
        $out = (array)parent::getConfigSchema();
        $pathSchema = (array)FilePublicPath::getConfigSchema();
        $pathSchema[1]['label'] = 'Root folder';
        $pathSchema[1]['description'] = 'Enter a full path for a root folder.';

        return array_merge($out, $pathSchema);
    }

    /**
     * {@inheritdoc}
     */
    protected static function prepareConfigSchemaField(array &$schema)
    {
        parent::prepareConfigSchemaField($schema);

        switch ($schema['name']) {
            case 'host':
                $schema['description'] = 'SFTP server host name or IP address.';
                break;
            case 'port':
                $schema['default'] = 22;
                $schema['description'] = 'SFTP server port number. Default is 22.';
                break;
            case 'username':
                $schema['description'] = 'SFTP server username.';
                break;
            case 'password':
                $schema['description'] = 'SFTP server user password.';
                break;
            case 'timeout':
                $schema['default'] = 90;
                $schema['description'] = 'Number of seconds before the connection will timeout. Default is 90 seconds.';
                break;
            case 'host_fingerprint':
                $schema['label'] = 'Host Finger Print';
                $schema['description'] = 'Finger print of the public key of the host you are connecting to.';
                break;
            case 'private_key':
                $schema['description'] = 'Private key (string or path to local file)';
                break;
            case 'passphrase':
                $schema['label'] = 'Private Key Passphrase';
                $schema['type'] = 'password';
                $schema['description'] = 'Passphrase for an encrypted private key. Leave blank if the key is not encrypted.';
                break;
        }
    }
}