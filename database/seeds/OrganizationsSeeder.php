<?php
use Illuminate\Database\Seeder;
use F3\models\Address;
use F3\components\Model;

class OrganizationsSeeder extends Seeder
{
    /**
     * List of organizations.
     */
    private $organizations = [[
        'type' => 'company',
        'name' => 'LBCX',
        'metadata' => null
    ], [
        'type' => 'merchant',
        'name' => 'Shopee',
        'metadata' => null
    ], [
        'type' => 'merchant',
        'name' => 'Lazada',
        'metadata' => null
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
        'metadata' => [
            'barcode_format' => 'code_128'
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
        'metadata' => [
            'barcode_format' => 'qr'
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
        'metadata' => [
            'barcode_format' => 'qr'
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
        'metadata' => [
            'barcode_format' => 'qr'
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
        'metadata' => [
            'barcode_format' => 'qr'
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
        'metadata' => [
            'barcode_format' => 'qr'
        ],
    ], [
        'type' => 'merchant',
        'name' => 'CMO',
        'metadata' => null
    ], [
        'type' => 'merchant',
        'name' => 'Shipping Cart',
        'metadata' => null
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

            // Insert the organizations.
            foreach ($this->organizations as $org) {
                // Check if the party exists.
                $party_id = DB::table('core.organizations')->where('name', $org['name'])->value('party_id');

                if ($party_id) {
                    // The organization exist. Update it.
                    DB::table('core.parties')->where('id', $party_id)->update(['metadata' => ($org['metadata']) ? json_encode($org['metadata']) : null]);
                } else {
                    // The organization does not exist. Create it.
                    $party_id = DB::table('core.parties')->insertGetId(['type' => 'organization', 'metadata' => ($org['metadata']) ? json_encode($org['metadata']) : null, 'created_at' => 'now()']);
                }

                // Create the organization.
                DB::table('core.organizations')->updateOrInsert(['party_id' => $party_id], ['party_id' => $party_id, 'type' => $org['type'], 'name' => $org['name']]);

                // Create three keys for the organization.
                $result = DB::table('core.api_keys')->where('party_id', $party_id)->get();

                if (!$result) {
                    if ($org['type'] == 'merchant') {
                        for ($i = 0; $i < 3; $i++) {
                            // Generate a key.
                            $key = __generate_api_key($party_id);

                            // Create the keys.
                            DB::table('core.api_keys')->updateOrInsert(['party_id' => $party_id, 'api_key' => $key['api_key'], 'secret_key' => $key['secret_key'], 'created_at' => 'now()', 'expires_at' => $key['expires_at']]);
                        }
                    }
                }

                // Get the location ID of PH.
                $country_id = DB::table('core.locations')->where([['type', 'country'], ['code', 'PH']])->value('id');

                // Create the warehouse address.
                if ($org['type'] == 'courier') {
                    DB::table('core.addresses')->updateOrInsert(['party_id' => $party_id, 'type' => 'warehouse'], array_merge(
                        $org['warehouse'], [
                            'hash' => Address::hash(array_merge($org['warehouse'], ['party_id' => $party_id])),
                            'type' => 'warehouse',
                            'party_id' => $party_id,
                            'country_id' => $country_id,
                        ]
                    ));
                }

                // Create the roles.
                if ($org['type'] == 'merchant') {
                    // Get the client role.
                    $role_id = DB::table('core.roles')->where('name', 'client')->value('id');
                    DB::table('core.party_roles')->updateOrInsert(['party_id' => $party_id, 'role_id' => $role_id], [
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
