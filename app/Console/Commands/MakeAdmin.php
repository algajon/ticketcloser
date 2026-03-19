<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'make:admin {email : The email of the user to promote}';
    protected $description = 'Grant admin privileges to a user by email';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email [{$email}] not found.");
            return self::FAILURE;
        }

        if ($user->is_admin) {
            $this->info("{$user->name} ({$email}) is already an admin.");
            return self::SUCCESS;
        }

        $user->update(['is_admin' => true]);
        $this->info("✅ {$user->name} ({$email}) is now an admin.");

        return self::SUCCESS;
    }
}
