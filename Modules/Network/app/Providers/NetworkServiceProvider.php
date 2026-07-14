<?php

namespace Modules\Network\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Modules\Network\Contracts\NetworkProviderInterface;
use Modules\Network\Adapters\DummyAdapter;
use Modules\Network\Adapters\MikroTikAdapter;
use Modules\Network\Adapters\FreeRadiusAdapter;
use Modules\Network\Adapters\GenieACSAdapter;
use Modules\Network\Adapters\HuaweiOLTAdapter;
use Modules\Network\Adapters\ZTEOLTAdapter;
use Modules\Network\Adapters\FiberhomeOLTAdapter;

class NetworkServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Network';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'network';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $provider = config('network.default', 'dummy');

        $this->app->bind(NetworkProviderInterface::class, function () use ($provider) {
            switch ($provider) {
                case 'mikrotik':
                    return new MikroTikAdapter();
                case 'freeradius':
                    return new FreeRadiusAdapter();
                case 'genieacs':
                    return new GenieACSAdapter();
                case 'huaweiolt':
                    return new HuaweiOLTAdapter();
                case 'zteolt':
                    return new ZTEOLTAdapter();
                case 'fiberhomeolt':
                    return new FiberhomeOLTAdapter();
                case 'cdataolt':
                    return new \Modules\Network\Adapters\CdataOLTAdapter();
                case 'hsgqolt':
                    return new \Modules\Network\Adapters\HsgqOLTAdapter();
                case 'dummy':
                default:
                    return new DummyAdapter();
            }
        });
    }

    /**
     * Define module schedules.
     * 
     * @param $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }
}
