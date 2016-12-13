<?php
use Illuminate\Database\Seeder;
use F3\components\Model;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * List of system users.
     */
    private $users = [[
        'login_id' => 'oms@lbcx.ph',
        'email' => 'oms@lbcx.ph',
        'password' => 'oms123',
        'first_name' => 'OMS',
        'last_name' => 'User',
        'roles' => ['oms'],
        'relationships' => [
            'employee_of' => 'LBCX'
        ]
    ], [
        'login_id' => 'finance@lbcx.ph',
        'email' => 'finance@lbcx.ph',
        'password' => 'finance123',
        'first_name' => 'Finance',
        'last_name' => 'User',
        'roles' => ['finance'],
        'relationships' => [
            'employee_of' => 'LBCX'
        ]
    ], [
        'login_id' => 'jbpalmos@lbcexpress.com',
        'email' => 'jbpalmos@lbcexpress.com',
        'password' => 'oms123',
        'first_name' => 'John Paul B. Palmos',
        'last_name' => 'John Paul B. Palmos',
        'roles' => ['oms'],
        'relationships' => [
            'employee_of' => 'LBCX'
        ]
    ], [
        'login_id' => 'jstorres@lbcexpress.com',
        'email' => 'jstorres@lbcexpress.com',
        'password' => 'oms123',
        'first_name' => 'Joshua S. Torres',
        'last_name' => 'Joshua S. Torres',
        'roles' => ['oms'],
        'relationships' => [
            'employee_of' => 'LBCX'
        ]
    ], [
        'login_id' => 'mbbacud@lbcexpress.com',
        'email' => 'mbbacud@lbcexpress.com',
        'password' => 'oms123',
        'first_name' => 'Marie Joanne B. Bacud',
        'last_name' => 'Marie Joanne B. Bacud',
        'roles' => ['oms'],
        'relationships' => [
            'employee_of' => 'LBCX'
        ]
    ], [
        'login_id' => 'ecbesa@lbcexpress.com',
        'email' => 'ecbesa@lbcexpress.com',
        'password' => 'oms123',
        'first_name' => 'Earlbert C. Besa',
        'last_name' => 'Earlbert C. Besa',
        'roles' => ['oms'],
        'relationships' => [
            'employee_of' => 'LBCX'
        ]
    ], [
        'login_id' => 'appalanca@lbcexpress.com',
        'email' => 'appalanca@lbcexpress.com',
        'password' => 'oms123',
        'first_name' => 'Alejandro John P. Palanca',
        'last_name' => 'Alejandro John P. Palanca',
        'roles' => ['oms'],
        'relationships' => [
            'employee_of' => 'LBCX'
        ]
    ], [
        'login_id' => 'cdelmundo@lbcx.ph',
        'email' => 'cdelmundo@lbcx.ph',
        'password' => 'oms123',
        'first_name' => 'Ma. Cecilia Cordero-del Mundo',
        'last_name' => 'Ma. Cecilia Cordero-del Mundo',
        'roles' => ['oms'],
        'relationships' => [
            'employee_of' => 'LBCX'
        ]
    ], [
        'login_id' => 'jettmarana@lbcx.ph',
        'email' => 'jettmarana@lbcx.ph',
        'password' => 'oms123',
        'first_name' => 'Jett Marana',
        'last_name' => 'Jett Marana',
        'roles' => ['oms'],
        'relationships' => [
            'employee_of' => 'LBCX'
        ]
    ]];

    /**
     * Execute the console command.
     * @return void
     */
    public function run()
    {
        try {
            // Start the transaction.
            DB::beginTransaction();

            // Create the users.
            foreach ($this->users as $user) {
                // Check if the party exists.
                $party_id = DB::table('core.users')->where('login_id', $user['login_id'])->value('party_id');

                if (!$party_id) {
                    // The party does not exist. Create it.
                    $party_id = DB::table('core.parties')->insertGetId(['type' => 'user', 'created_at' => 'now()']);
                }

                // Hash the password.
                $user['password'] = Hash::make($user['password']);

                // Create the user.
                DB::table('core.users')->updateOrInsert(['party_id' => $party_id], array_merge(array_only($user, ['login_id', 'email', 'password', 'first_name', 'last_name']), ['party_id' => $party_id]));

                // Create the roles.
                if (isset($user['roles'])) {
                    foreach ($user['roles'] as $role) {
                        // Look for the role.
                        $role_id = DB::table('core.roles')->where('name', $role)->value('id');

                        if (!$role_id) {
                            throw new \Exception('Role "' . $user['role'] . '" does not exsit.');
                        }

                        // Assign the role to the user.
                        DB::table('core.party_roles')->updateOrInsert(['party_id' => $party_id, 'role_id' => $role_id], [
                            'party_id' => $party_id,
                            'role_id' => $role_id
                        ]);
                    }
                }

                // Create the API keys.
                $result = DB::table('core.api_keys')->where('party_id', $party_id)->get();

                if (!$result->toArray()) {
                    foreach (['dev', 'staging', 'production'] as $env) {
                        // Generate a key.
                        $key = __generate_api_key($party_id);

                        // Create the keys.
                        DB::table('core.api_keys')->updateOrInsert(['party_id' => $party_id, 'name' => $env, 'api_key' => $key['api_key'], 'secret_key' => $key['secret_key'], 'created_at' => 'now()', 'expires_at' => $key['expires_at']]);
                    }
                }

                // Create the relationships.
                if (isset($user['relationships'])) {
                    foreach ($user['relationships'] as $type => $to_party) {
                        // Get the party ID.
                        $to_party_id = DB::table('core.organizations')->where('name', $to_party)->value('party_id');

                        // Create the relationship.
                        DB::table('core.relationships')->updateOrInsert(['from_party_id' => $party_id, 'type' => $type, 'to_party_id' => $to_party_id], ['from_party_id' => $party_id, 'type' => $type, 'to_party_id' => $to_party_id]);
                    }
                }
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
