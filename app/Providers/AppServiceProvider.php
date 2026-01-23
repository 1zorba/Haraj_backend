<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Policies\AdminPolicy;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
  // app/Providers/AuthServiceProvider.php

// ...


protected $policies = [
    // ...
    User::class => AdminPolicy::class,
];

// ...

}
