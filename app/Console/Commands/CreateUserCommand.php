<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateUserCommand extends Command
{
    protected $signature = 'app:create-user {name?} {email?} {--role=admin : User role (admin or cashier)}';

    protected $description = 'Create an Admin or Cashier account';

    public function handle(): int
    {
        $role = $this->option('role');
        if (! in_array($role, ['admin', 'cashier'], true)) {
            $this->error('Role must be admin or cashier.');

            return self::FAILURE;
        }

        $data = [
            'name' => $this->argument('name') ?: $this->ask('Name'),
            'email' => $this->argument('email') ?: $this->ask('Email'),
        ];
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
        ]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        $password = $this->secret('Password (at least 12 characters)');
        $confirmation = $this->secret('Confirm password');
        if (! is_string($password) || strlen($password) < 12 || $password !== $confirmation) {
            $this->error('Passwords must match and contain at least 12 characters.');

            return self::FAILURE;
        }

        User::create([...$data, 'password' => $password, 'role' => $role]);
        $this->info("{$role} account created for {$data['email']}.");

        return self::SUCCESS;
    }
}
