<?php
namespace STS\StorageConnect\Providers\Traits;
use Illuminate\Http\RedirectResponse;
use SocialiteProviders\Manager\OAuth2\User;
use StorageConnect;
use STS\StorageConnect\StorageConnectManager;

/**
 * Class EnhancedProvider
 * @package STS\StorageConnect\Providers\Traits
 */
trait EnhancedProvider
{
    /**
     * @var array
     */
    protected $fullConfig;

    /**
     * @var StorageConnectManager
     */
    protected $manager;

    /**
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * @var User
     */
    protected $user;


    /**
     * @var array
     */
    protected $connection = [];

    /**
     * EnhancedProvider constructor.
     *
     * @param array                 $config
     * @param StorageConnectManager $manager
     * @param                       $app
     */
    public function __construct(array $config, StorageConnectManager $manager, $app)
    {
        $this->fullConfig = $config;
        $this->manager = $manager;
        $this->app = $app;

        parent::__construct(
            $this->app['request'], $config['client_id'],
            $config['client_secret'], $this->callbackUrl(),
            array_get($config, 'guzzle', [])
        );
    }

    /**
     * @return string
     */
    protected function callbackUrl()
    {
        return sprintf("https://%s/%s/callback/%s",
            $this->app['config']->get('storage-connect.callback_domain', $this->app['request']->getHost()),
            $this->app['config']->get('storage-connect.route'),
            $this->name()
        );
    }

    /**
     * @return string
     */
    protected function getState()
    {
        return base64_encode(json_encode(array_merge(
            ['csrf' => str_random(40)],
            $this->manager->getCustomState($this->name())
        )));
    }

    /**
     * @return RedirectResponse
     */
    public function finish()
    {
        $this->manager->saveConnectedStorage($this->serializeUser($this->user()), $this->name());

        return new RedirectResponse($this->fullConfig['redirect_after_connect']);
    }

    /**
     * @return User
     */
    public function user()
    {
        if(!$this->user) {
            $this->user = parent::user();
        }

        return $this->user;
    }

    /**
     * @return string
     */
    public function name()
    {
        return strtolower(self::IDENTIFIER);
    }

    /**
     * @param array $connection
     *
     * @return $this
     */
    public function load(array $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->connection;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return $this->__toString();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->toArray());
    }
}