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
            'permissions' => ['create-order', 'view-order', 'view-orders', 'edit-order', 'upload-orders', 'create-claim', 'view-claim', 'view-claims', 'edit-claims', 'view-wallets', 'view-wallet', 'create-bank-account', 'view-bank-accounts', 'view-bank-account', 'edit-bank-account', 'delete-bank-account', 'manage-party'],
        ],
        'finance' => [
            'name' => 'finance',
            'display_name' => 'Finance',
            'permissions' => ['create-order', 'view-order', 'view-orders', 'edit-order', 'upload-orders', 'create-claim', 'view-claim', 'view-claims', 'edit-claims', 'view-wallets', 'view-wallet', 'create-bank-account', 'view-bank-accounts', 'view-bank-account', 'edit-bank-account', 'delete-bank-account', 'manage-party'],
        ],
        'oms' => [
            'name' => 'oms',
            'display_name' => 'OMS',
            'permissions' => ['create-order', 'view-order', 'view-orders', 'edit-order', 'upload-orders', 'create-claim', 'view-claim', 'view-claims', 'edit-claims', 'manage-party'],
        ],
        'client' => [
            'name' => 'client',
            'display_name' => 'Client System',
            'permissions' => ['create-order', 'view-order', 'view-orders', 'edit-order', 'view-wallets', 'view-wallet', 'create-bank-account', 'view-bank-accounts', 'view-bank-account', 'edit-bank-account', 'delete-bank-account', 'view-claims', 'view-claim'],
        ],
        'courier' => [
            'name' => 'courier',
            'display_name' => 'Courier',
            'permissions' => ['create-order', 'view-order', 'view-orders', 'edit-order', 'view-wallets', 'view-wallet', 'view-claims', 'view-claim'],
        ],
        'hub' => [
            'name' => 'hub',
            'display_name' => 'hub',
            'permissions' => ['create-order', 'view-order', 'view-orders', 'edit-order', 'view-wallets', 'view-wallet', 'view-claims', 'view-claim'],
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
