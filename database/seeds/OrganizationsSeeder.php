<?php
use Illuminate\Database\Seeder;
use F3\models\Address;

class OrganizationsSeeder extends Seeder
{
    /**
     * List of organizations.
     */
    private $organizations = [[
        'type' => 'company',
        'name' => 'LBCX'
    ], [
        'type' => 'merchant',
        'name' => 'Shopee'
    ], [
        'type' => 'merchant',
        'name' => 'Lazada'
    ], [
        'type' => 'courier',
        'name' => 'LBC',
        'warehouse' => [
            'name' => 'LBC',
            'line_1' => 'LBC Express',
            'city' => 'Pasay',
            'state' => 'Manila',
            'postal_code' => '1301',
        ],
    ], [
        'type' => 'courier',
        'name' => 'LBCX Yakal',
        'warehouse' => [
            'name' => 'LBCX Yakal',
            'line_1' => 'VersaPrint, Inc., 7452 A. Yakal corner Bakawan Street',
            'city' => 'Makati',
            'state' => 'Manila',
            'postal_code' => '1203',
        ],
    ], [
        'type' => 'courier',
        'name' => 'LBCX QC',
        'warehouse' => [
            'name' => 'LBCX QC',
            'line_1' => 'LBCX QC',
            'city' => 'Quezon City',
            'state' => 'Manila',
            'postal_code' => '1100',
        ],
    ], [
        'type' => 'courier',
        'name' => 'LBCX Park Square',
        'warehouse' => [
            'name' => 'LBCX Park Square',
            'line_1' => 'LBCX Park Square',
            'city' => 'Makati',
            'state' => 'Manila',
            'postal_code' => '1226',
        ],
    ], [
        'type' => 'courier',
        'name' => 'LBCX Greenhills',
        'warehouse' => [
            'name' => 'LBCX Greenhills',
            'line_1' => 'LBCX Greenhills',
            'city' => 'San Juan',
            'state' => 'Manila',
            'postal_code' => '1500',
        ],
    ], [
        'type' => 'courier',
        'name' => 'LBCX Cebu',
        'warehouse' => [
            'name' => 'LBCX Cebu',
            'line_1' => 'LBCX Cebu',
            'city' => 'Cebu',
            'state' => 'Cebu',
            'postal_code' => '6000',
        ],
    ], [
        'type' => 'merchant',
        'name' => 'CMO',
    ], [
        'type' => 'merchant',
        'name' => 'Shipping Cart',
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

            // Reset the tables.
            DB::statement('TRUNCATE TABLE core.parties, core.organizations RESTART IDENTITY CASCADE');

            // Insert the organizations.
            foreach ($this->organizations as $org) {
                $party_id = DB::table('core.parties')->insertGetId(['type' => 'organization', 'created_at' => 'now()']);
                DB::table('core.organizations')->insert(['party_id' => $party_id, 'type' => $org['type'], 'name' => $org['name']]);

                // Create three keys for the organization.
                if ($org['type'] == 'merchant') {
                    for ($i = 0; $i < 3; $i++) {
                        // Generate a key.
                        $key = __generate_api_key($party_id);

                        // Create the keys.
                        DB::table('core.api_keys')->insert(['party_id' => $party_id, 'api_key' => $key['api_key'], 'secret_key' => $key['secret_key'], 'created_at' => 'now()', 'expires_at' => $key['expires_at']]);
                    }
                }

                // Get the location ID of PH.
                $location_id = DB::table('core.locations')->where([['type', 'country'], ['code', 'PH']])->value('id');

                // Create the warehouse address.
                if ($org['type'] == 'courier') {
                    DB::table('core.addresses')->insert(array_merge(
                        $org['warehouse'], [
                            'hash' => Address::hash(array_merge($org['warehouse'], ['party_id' => $party_id])),
                            'type' => 'warehouse',
                            'party_id' => $party_id,
                            'location_id' => $location_id,
                        ]
                    ));
                }

                // Create the roles.
                if ($org['type'] == 'merchant') {
                    // Get the client role.
                    $role_id = DB::table('core.roles')->where('name', 'client')->value('id');
                    DB::table('core.party_roles')->insert([
                        'party_id' => $party_id,
                        'role_id' => $role_id
                    ]);
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
