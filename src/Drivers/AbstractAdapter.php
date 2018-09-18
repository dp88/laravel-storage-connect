<?php
namespace STS\StorageConnect\Drivers;

use Illuminate\Http\RedirectResponse;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use STS\StorageConnect\Events\CloudStorageSetup;
use STS\StorageConnect\Models\CloudStorage;
use STS\StorageConnect\Models\CustomManagedCloudStorage;
use STS\StorageConnect\Types\Quota;

abstract class AbstractAdapter
{
    /**
     * @var
     */
    protected $config;

    /**
     * @var AbstractProvider
     */
    protected $provider;

    /**
     * @var mixed
     */
    protected $service;

    /**
     * @var array
     */
    protected $token;

    /**
     * @var callable
     */
    protected $tokenUpdateCallback;

    /**
     * DropboxAdapter constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param $token
     * @param null $callback
     *
     * @return $this
     * @paran $callback
     *
     */
    public function setToken($token, $callback = null)
    {
        $this->token = $token;
        $this->tokenUpdateCallback = $callback;

        return $this;
    }

    /**
     * @param $token
     */
    protected function updateToken($token)
    {
        if($this->tokenUpdateCallback) {
            call_user_func($this->tokenUpdateCallback, $token);
        }
    }

    /**
     * @param CloudStorage $storage
     * @param null $redirectUrl
     *
     * @return RedirectResponse
     */
    public function authorize(CloudStorage $storage, $redirectUrl = null)
    {
        if(!$storage->exists) {
            $storage->save();
        }

        if($storage instanceof CustomManagedCloudStorage) {
            $this->provider()->session()->put('storage-connect.custom', true);
        } else {
            $this->provider()->session()->put('storage-connect.id', $storage->id);
        }

        if($redirectUrl != null) {
            $this->provider()->session()->put('storage-connect.redirect', $redirectUrl);
        }

        return $this->provider()->redirect();
    }

    /**
     * @param CloudStorage $storage
     *
     * @return void
     */
    public function finish(CloudStorage $storage)
    {
        $this->setToken($this->provider()->user()->accessTokenResponseBody);

        $storage->update(array_merge(
            [
                'token' => $this->provider()->user()->accessTokenResponseBody,
                'connected' => 1,
                'enabled' => 1
            ],
            $this->mapUserDetails($this->provider()->user())
        ));
        $storage->updateQuota($this->getQuota());

        event(new CloudStorageSetup($storage));
    }

    /**
     * @return string
     */
    public function driver()
    {
        return $this->driver;
    }

    /**
     * @return AbstractProvider
     */
    public function provider()
    {
        if(!$this->provider) {
            $this->setProvider($this->makeProvider());
        }

        return $this->provider;
    }

    public function setProvider(AbstractProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @return mixed
     */
    public function service()
    {
        if(!$this->service) {
            $this->setService($this->makeService());
        }

        return $this->service;
    }

    /**
     * @param $service
     */
    public function setService($service)
    {
        $this->service = $service;
    }

    /**
     * @param $method
     * @param $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->service()->$method(...$parameters);
    }

    /**
     * @return AbstractProvider
     */
    abstract protected function makeProvider();

    /**
     * @return mixed
     */
    abstract protected function makeService();

    /**
     * @return Quota
     */
    abstract function getQuota();

    /**
     * @param $user
     *
     * @return array
     */
    abstract protected function mapUserDetails($user);

    /**
     * @param $sourcePath
     * @param $destinationPath
     *
     * @return mixed
     */
    abstract function upload($sourcePath, $destinationPath);
}