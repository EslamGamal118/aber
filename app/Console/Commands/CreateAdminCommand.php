<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->ask('Enter admin name');
        $email = $this->ask('Enter admin email');
        $password = $this->secret('Enter admin password');
        $confirmPassword = $this->secret('Confirm password');
        
        if ($password !== $confirmPassword) {
            $this->error('Passwords do not match!');
            return 1;
        }
        
        try {
            $admin = \App\Models\Admin::create([
                'name' => $name,
                'email' => $email,
                'password' => \Illuminate\Support\Facades\Hash::make($password),
                'status' => 'active',
            ]);
            
            $this->info("Admin created successfully with ID: {$admin->id}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to create admin: {$e->getMessage()}");
            return 1;
        }
    }
}
