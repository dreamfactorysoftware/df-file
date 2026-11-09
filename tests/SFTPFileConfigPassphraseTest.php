<?php

use DreamFactory\Core\Facades\ServiceManager;
use DreamFactory\Core\File\Components\SFTPFileSystem;
use DreamFactory\Core\File\Models\SFTPFileConfig;
use DreamFactory\Core\Models\Service;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use phpseclib3\Crypt\EC;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Exception\NoKeyLoadedException;

class SFTPFileConfigPassphraseTest extends \DreamFactory\Core\Testing\TestCase
{
    protected static $staged = false;

    private $services = [];
    private $privateKey;
    private $encryptedKey;

    public function setUp(): void
    {
        parent::setUp();

        $key = EC::createKey('Ed25519');
        $this->privateKey = $key->toString('PKCS8');
        $this->encryptedKey = $key->withPassword('s3cret')->toString('OpenSSH');
    }

    protected function tearDown(): void
    {
        foreach ($this->services as $service) {
            $service->delete();
        }

        parent::tearDown();
    }

    private function createService(array $config = [])
    {
        $service = Service::create([
            'name' => 'sftp_passphrase_' . uniqid(),
            'label' => 'SFTP passphrase test',
            'is_active' => true,
            'type' => 'sftp_file',
            'config' => array_merge([
                'host' => 'sftp.example.test',
                'username' => 'test',
                'password' => 'login-password',
                'container' => '/upload'
            ], $config)
        ]);
        $this->services[] = $service;

        return $service;
    }

    private function connectionProvider(SFTPFileSystem $filesystem)
    {
        $adapter = $this->getNonPublicProperty($filesystem, 'adapter');

        $property = new ReflectionProperty(SftpAdapter::class, 'connectionProvider');

        return $property->getValue($adapter);
    }

    private function serviceProvider(Service $service)
    {
        return $this->connectionProvider(ServiceManager::getService($service->name)->driver());
    }

    public function testConfigSchemaHasPassphrasePasswordField()
    {
        $schema = SFTPFileConfig::getConfigSchema();
        $fields = array_column($schema, null, 'name');
        $this->assertArrayHasKey('passphrase', $fields);
        $this->assertSame('password', $fields['passphrase']['type']);
        $this->assertSame('Private Key Passphrase', $fields['passphrase']['label']);
        $this->assertNotEmpty($fields['passphrase']['description']);
        $names = array_column($schema, 'name');
        $this->assertSame(array_search('private_key', $names) + 1, array_search('passphrase', $names));
    }

    public function testPassphraseIsFillableEncryptedAndProtected()
    {
        $config = new SFTPFileConfig();
        $this->assertContains('passphrase', $config->getFillable());
        $this->assertContains('passphrase', $config->getEncryptable());
        $this->assertContains('passphrase', $config->getProtectable());
        $this->assertContains('private_key', $config->getEncryptable());
    }

    public function testPassphraseAndPrivateKeyEncryptedAtRest()
    {
        $service = $this->createService(['private_key' => $this->encryptedKey, 'passphrase' => 's3cret']);
        $row = (array)DB::table('sftp_service_config')->where('service_id', $service->id)->first();
        $this->assertArrayHasKey('passphrase', $row);
        foreach (['passphrase' => 's3cret', 'private_key' => $this->encryptedKey] as $field => $value) {
            $this->assertNotSame($value, $row[$field]);
            $this->assertSame($value, Crypt::decrypt($row[$field]));
            $this->assertSame($value, SFTPFileConfig::getConfig($service->id, null, false)[$field]);
        }
        $protected = SFTPFileConfig::getConfig($service->id);
        $this->assertSame('**********', $protected['passphrase']);
        SFTPFileConfig::setConfig($service->id, $protected);
        $this->assertSame('s3cret', SFTPFileConfig::getConfig($service->id, null, false)['passphrase']);
        $this->assertSame($this->encryptedKey, Crypt::decrypt(
            DB::table('sftp_service_config')->where('service_id', $service->id)->value('private_key')
        ));
    }

    public function testServicePassesPassphraseToConnectionProvider()
    {
        $service = $this->createService(['private_key' => $this->encryptedKey, 'passphrase' => 's3cret']);
        $provider = $this->serviceProvider($service);
        $passphrase = $this->getNonPublicProperty($provider, 'passphrase');
        $privateKey = $this->getNonPublicProperty($provider, 'privateKey');
        $this->assertSame('s3cret', $passphrase);
        $this->assertSame($this->encryptedKey, $privateKey);
        $this->assertSame('login-password', $this->getNonPublicProperty($provider, 'password'));
        $this->assertInstanceOf(EC\PrivateKey::class, PublicKeyLoader::load($privateKey, $passphrase));
        $this->expectException(NoKeyLoadedException::class);
        PublicKeyLoader::load($privateKey);
    }

    public function testEmptyPassphraseBecomesNull()
    {
        $filesystem = new SFTPFileSystem([
            'host' => 'sftp.example.test',
            'username' => 'test',
            'private_key' => $this->privateKey,
            'passphrase' => '',
            'host_fingerprint' => null,
            'root' => '/upload'
        ]);
        $provider = $this->connectionProvider($filesystem);
        $this->assertNull($this->getNonPublicProperty($provider, 'passphrase'));
    }

    public function testLegacyPlaintextPrivateKeyStillReads()
    {
        foreach (['/path/to/id_rsa', $this->privateKey] as $privateKey) {
            $service = $this->createService();
            DB::table('sftp_service_config')->where('service_id', $service->id)->delete();
            DB::table('sftp_service_config')->insert([
                'service_id' => $service->id,
                'host' => 'sftp.example.test',
                'username' => 'test',
                'private_key' => $privateKey
            ]);
            $this->assertSame($privateKey, SFTPFileConfig::getConfig($service->id, null, false)['private_key']);
            $this->assertSame($privateKey, SFTPFileConfig::find($service->id)->private_key);
            $provider = $this->serviceProvider($service);
            $this->assertSame($privateKey, $this->getNonPublicProperty($provider, 'privateKey'));
        }
    }

    public function testPasswordOnlyAndKeyOnlyConfigsUnchanged()
    {
        $provider = $this->serviceProvider($this->createService());
        $this->assertNull($this->getNonPublicProperty($provider, 'privateKey'));
        $this->assertNull($this->getNonPublicProperty($provider, 'passphrase'));
        $this->assertSame('login-password', $this->getNonPublicProperty($provider, 'password'));

        $provider = $this->serviceProvider($this->createService([
            'private_key' => $this->privateKey,
            'password' => null
        ]));
        $this->assertSame($this->privateKey, $this->getNonPublicProperty($provider, 'privateKey'));
        $this->assertNull($this->getNonPublicProperty($provider, 'passphrase'));
        $this->assertNull($this->getNonPublicProperty($provider, 'password'));
    }
}
