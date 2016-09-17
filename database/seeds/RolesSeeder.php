<?php
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * List of roles.
     */
    private $roles = [
        'admin' => [
            'name' => 'admin',
            'display_name' => 'Administrator',
            'permissions' => ['create-user', 'edit-user', 'enable-user', 'disable-user'],
            'parent_id' => null,
        ],
        'finance' => [
            'name' => 'finance',
            'display_name' => 'Finance',
            'permissions' => ['view-transactions', 'view-transaction', 'upload-transactions', 'edit-transaction'],
            'parent_id' => 1,
        ],
        'oms' => [
            'name' => 'oms',
            'display_name' => 'OMS',
            'permissions' => ['view-orders', 'view-order', 'upload-orders', 'edit-order'],
            'parent_id' => 1,
        ],
        'client' => [
            'name' => 'client',
            'display_name' => 'Client System',
            'permissions' => ['create-order'],
            'parent_id' => 1
        ]
    ];

    /**
     * Execute the console command.
     * @return void
     */
    public function run()
    {
        try {
            // Start the transaction.
            DB::beginTransaction();

            // Reset the tables.
            DB::statement('TRUNCATE TABLE core.roles RESTART IDENTITY CASCADE');

            // Insert the roles.
            foreach ($this->roles as $role) {
                DB::table('core.roles')->insert([
                    'name' => $role['name'],
                    'display_name' => $role['display_name'],
                    'permissions' => json_encode($role['permissions']),
                    'parent_id' => $role['parent_id'],
                    'created_at' => 'now()'
                ]);
            }

            // Commit.
            DB::commit();
        } catch (Exception $e) {
            // Rollback.
            DB::rollBack();

            // Display the error.
            echo $e->getCode() . ': ' . $e->getMessage() . "\n";
        }
    }
}