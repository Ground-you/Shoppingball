<?php

namespace App\Providers;

use App\Payments\ManualPaymentGateway;
use App\Payments\PaymentGateway;
use App\Payments\TossPaymentsGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentGateway::class, function () {
            if (config('payments.driver') === 'toss') {
                return new TossPaymentsGateway(
                    (string) config('payments.toss.secret_key'),
                    (string) config('payments.toss.api_url'),
                );
            }

            return new ManualPaymentGateway();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
