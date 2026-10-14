<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Application\UseCases\ProvisionInitialAdministrator;

Artisan::command('library:provision-admin', function (ProvisionInitialAdministrator $provision): int {
    $created = $provision->execute(
        (string) config('library.admin_username'),
        (string) config('library.admin_password'),
    );

    $this->components->info($created ? 'Administrador inicial provisionado.' : 'El administrador inicial ya estaba provisionado.');

    return 0;
})->purpose('Create the initial administrator from deployment environment credentials');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
