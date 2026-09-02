<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    public function test_it_creates_the_admin_user_from_the_environment(): void
    {
        config([
            'app.admin.name' => 'Administrator',
            'app.admin.email' => 'seeder-test@example.test',
            'app.admin.password' => 'rahasia123',
        ]);

        $this->seed(AdminUserSeeder::class);

        $user = User::where('email', 'seeder-test@example.test')->first();

        $this->assertNotNull($user);
        $this->assertSame('Administrator', $user->name);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
    }

    public function test_running_it_twice_does_not_duplicate_the_user(): void
    {
        config([
            'app.admin.name' => 'Administrator',
            'app.admin.email' => 'seeder-test@example.test',
            'app.admin.password' => 'rahasia123',
        ]);

        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->assertSame(1, User::where('email', 'seeder-test@example.test')->count());
    }
}
