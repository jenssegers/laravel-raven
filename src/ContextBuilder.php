<?php namespace Jenssegers\Raven;

class ContextBuilder
{
    /**
     * @var Container
     */
    protected $app;

    /**
     * @param Container $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Build context data for the current request.
     *
     * @return array
     */
    public function build(array $context)
    {
        // Add auth data if available.
        if (isset($this->app['auth']) && $user = $this->app['auth']->user()) {
            if (empty($context['user']) or ! is_array($context['user'])) {
                $context['user'] = [];
            }

            if (! isset($context['user']['id']) && method_exists($user, 'getAuthIdentifier')) {
                $context['user']['id'] = $user->getAuthIdentifier();
            }

            if (! isset($context['user']['id']) && method_exists($user, 'getKey')) {
                $context['user']['id'] = $user->getKey();
            }

            if (! isset($context['user']['id']) && isset($user->id)) {
                $context['user']['id'] = $user->id;
            }
        }

        // Add session data if available.
        if (isset($this->app['session']) && $session = $this->app['session']->all()) {
            if (empty($context['user']) or ! is_array($context['user'])) {
                $context['user'] = [];
            }

            if (! isset($context['user']['id'])) {
                $context['user']['id'] = $this->app->session->getId();
            }

            if (isset($context['user']['data'])) {
                $context['user']['data'] = array_merge($session, $context['user']['data']);
            } else {
                $context['user']['data'] = $session;
            }
        }

        // Automatic tags
        $tags = [
            'environment' => $this->app->environment(),
            'server'      => $this->app->request->server('HTTP_HOST'),
            'php_version' => phpversion(),
        ];

        // Add tags to context.
        if (isset($context['tags'])) {
            $context['tags'] = array_merge($tags, $context['tags']);
        } else {
            $context['tags'] = $tags;
        }

        // Automatic extra data.
        $extra = [
            'ip' => $this->app->request->getClientIp(),
        ];

        // Everything that is not 'user', 'tags' or 'level' is automatically considered
        // as additonal 'extra' context data.
        $extra = array_merge($extra, array_except($context, ['user', 'tags', 'level', 'extra']));

        // Add extra to context.
        if (isset($context['extra'])) {
            $context['extra'] = array_merge($extra, $context['extra']);
        } else {
            $context['extra'] = $extra;
        }

        // Clean out other values from context.
        $context = array_only($context, ['user', 'tags', 'level', 'extra']);

        return $context;
    }
}
