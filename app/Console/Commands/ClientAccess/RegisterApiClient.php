<?php

namespace App\Console\Commands\ClientAccess;

use App\Models\ClientAccess\ApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RegisterApiClient extends Command
{
    protected $signature = 'client-access:register {app_name? : Nama aplikasi yang akan mengakses API}';

    protected $description = 'Daftarkan aplikasi klien baru dan cetak bearer token-nya sekali';

    public function handle(): int
    {
        $appName = $this->argument('app_name');

        if (blank($appName)) {
            $appName = $this->ask('Nama aplikasi yang akan mengakses API');
        }

        $appName = trim((string) $appName);

        if ($appName === '') {
            $this->error('Nama aplikasi tidak boleh kosong.');

            return self::FAILURE;
        }

        if (ApiClient::where('app_name', $appName)->exists()) {
            $this->error("Aplikasi \"{$appName}\" sudah terdaftar. Pakai nama lain.");

            return self::FAILURE;
        }

        $token = Str::random(64);

        ApiClient::create([
            'app_name' => $appName,
            'token' => hash('sha256', $token),
            'is_active' => true,
        ]);

        $this->info("Aplikasi \"{$appName}\" terdaftar.");
        $this->newLine();
        $this->line('Bearer token:');
        $this->line($token);
        $this->newLine();
        $this->warn('Simpan token ini sekarang. Database hanya menyimpan hash-nya, jadi token di atas tidak bisa dilihat lagi.');

        return self::SUCCESS;
    }
}
