<?php namespace Jenssegers\Raven;

use Illuminate\Contracts\Container\Container;

class ContextBuilder
{
    /**
     * @var Container
     */
    protected $app;

    /**
     * @param Container $app
     */
    public function __construct(Container $app)
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
        // Add session data if available.
        if (isset($this->app['session']) && $session = $this->app['session']->all()) {
            if (empty($context['user']) or ! is_array($context['user'])) {
                $context['user'] = [];
            }

            if (isset($context['user']['data'])) {
                $context['user']['data'] = array_merge($session, $context['user']['data']);
            } else {
                $context['user']['data'] = $session;
            }

            // User session id as user id if not set.
            if (! isset($context['user']['id'])) {
                $context['user']['id'] = $this->app->session->getId();
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
