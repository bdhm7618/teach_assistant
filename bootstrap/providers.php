<?php


return [
    App\Providers\AppServiceProvider::class,
    App\Providers\RouteServiceProvider::class,
    Modules\Channel\App\Providers\ChannelServiceProvider::class,
    Modules\Core\Providers\CoreServiceProvider::class,
    Modules\Admin\Providers\AdminServiceProvider::class,
    Modules\Academic\App\Providers\AcademicServiceProvider::class
];
