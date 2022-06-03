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

        $this->checkApiKeySet();
    }

    protected function checkApiKeySet(): bool
    {
        $akismet_api_key = config('statamic.akismet.api_key');

        if (!$akismet_api_key) {
            \Statamic\Facades\CP\Toast::error('Akismet api key not set (error may take some time to go away once fixed).')
                ->duration(50000);
            return false;
        }
        return true;
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
                ->icon('<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
              </svg>')
                ->section('Form Spam')
                ->route('spam.index');
        });
        return $this;
    }
}
