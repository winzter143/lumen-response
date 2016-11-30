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
        ],
        'finance' => [
            'name' => 'finance',
            'display_name' => 'Finance',
            'permissions' => ['view-orders', 'view-order', 'upload-transactions', 'edit-transaction', 'view-claims', 'view-claim', 'edit-claims', 'view-wallets', 'view-wallet', 'view-bank-accounts', 'view-bank-account'],
        ],
        'oms' => [
            'name' => 'oms',
            'display_name' => 'OMS',
            'permissions' => ['view-orders', 'view-order', 'upload-orders', 'edit-order', 'view-claims', 'view-claim', 'edit-claims'],
        ],
        'client' => [
            'name' => 'client',
            'display_name' => 'Client System',
            'permissions' => ['create-order', 'view-own-orders', 'view-own-order', 'edit-own-order'],
        ],
        'courier' => [
            'name' => 'courier',
            'display_name' => 'Courier',
            'permissions' => ['view-orders', 'view-order', 'edit-order'],
        ],
        'hub' => [
            'name' => 'hub',
            'display_name' => 'hub',
            'permissions' => ['view-orders', 'view-order', 'edit-order'],
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

            // Insert the roles.
            foreach ($this->roles as $role) {
                DB::table('core.roles')->updateOrInsert(['name' => $role['name']], [
                    'name' => $role['name'],
                    'display_name' => $role['display_name'],
                    'permissions' => json_encode($role['permissions']),
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
