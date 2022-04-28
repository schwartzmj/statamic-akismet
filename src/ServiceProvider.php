<?php

namespace Schwartzmj\StatamicAkismet;

use Statamic\Providers\AddonServiceProvider;

use Statamic\CP\Navigation\Nav as StatamicNav;
use Statamic\Facades\CP\Nav as NavFacade;

class ServiceProvider extends AddonServiceProvider
{

    protected $listen = [
        \Statamic\Events\FormSubmitted::class => [
            Listeners\FormSubmitted::class,
        ],
    ];

    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
        //  'actions' => __DIR__.'/../routes/actions.php',
        //  'web' => __DIR__.'/../routes/web.php',
    ];

    protected $widgets = [
        //
    ];

    public function bootAddon()
    {
        $this->bootAddonConfig()
            ->bootAddonNav();
    }

    protected function bootAddonConfig(): self
    {
        $this->mergeConfigFrom(__DIR__.'/../config/akismet.php', 'statamic.akismet');

        $this->publishes([
            __DIR__.'/../config/akismet.php' => config_path('statamic/akismet.php'),
        ], 'akismet-config');

        return $this;
    }

    protected function bootAddonNav(): self {

        NavFacade::extend(function (StatamicNav $nav) {
            $nav->content('Spam')
                ->section('Form Spam')
                ->route('spam.index');
        });
        return $this;
    }
}
