<?php
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(CurrenciesSeeder::class);
        $this->call(RolesSeeder::class);
        $this->call(OrganizationsSeeder::class);
        $this->call(UsersSeeder::class);
    }
}
