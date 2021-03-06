<?php
use Illuminate\Database\Seeder;
use F3\Models\Address;
use F3\Components\Model;

class OrganizationsSeeder extends Seeder
{
    /**
     * List of organizations.
     */
    private $organizations = [[
        'name' => 'LBCX',
        'role' => 'courier',
        'metadata' => [
            'priority' => 1,
            'barcode_format' => 'qr',
        ],
        'wallets' => [
            'sales' => [
                'currency' => 'PHP',
                'amount' => 0,
                'credit_limit' => 0,
                'max_limit' => null
            ],
            'collections' => [
                'currency' => 'PHP',
                'amount' => 0,
                // TODO: Determine the correct credit limit.
                'credit_limit' => null,
                'max_limit' => null
            ]
        ]
    ], [
        'name' => 'LBC',
        'role' => 'courier',
        'metadata' => [
            'priority' => 2,
            'barcode_format' => 'code_128',
        ]
    ], [
        'name' => 'Shopee',
        'role' => 'client',
        'metadata' => null,
        'wallets' => [
            'fund' => [
                'currency' => 'PHP',
                'amount' => 0,
                'credit_limit' => 0,
                'max_limit' => null
            ],
            'settlement' => [
                'currency' => 'PHP',
                'amount' => 0,
                'credit_limit' => 0,
                'max_limit' => null
            ]
        ]
    ], [
        'name' => 'LBCX Test Merchant',
        'role' => 'client',
        'metadata' => null,
        'wallets' => [
            'fund' => [
                'currency' => 'PHP',
                'amount' => 0,
                'credit_limit' => 0,
                'max_limit' => null
            ],
            'settlement' => [
                'currency' => 'PHP',
                'amount' => 0,
                'credit_limit' => 0,
                'max_limit' => null
            ]
        ]
    ], [
        'name' => 'CMO',
        'role' => 'client',
        'metadata' => null,
        'wallets' => [
            'fund' => [
                'currency' => 'PHP',
                'amount' => 0,
                'credit_limit' => 0,
                'max_limit' => null
            ],
            'settlement' => [
                'currency' => 'PHP',
                'amount' => 0,
                'credit_limit' => 0,
                'max_limit' => null
            ]
        ]
    ], [
        'name' => 'Shipping Cart',
        'role' => 'client',
        'metadata' => null,
        'wallets' => [
            'fund' => [
                'currency' => 'PHP',
                'amount' => 0,
                'credit_limit' => 0,
                'max_limit' => null
            ],
            'settlement' => [
                'currency' => 'PHP',
                'amount' => 0,
                'credit_limit' => 0,
                'max_limit' => null
            ]
        ]
    ], [
        'name' => 'i4 Asia',
        'role' => 'client',
        'metadata' => null,
        'wallets' => [
            'fund' => [
                'currency' => 'PHP',
                'amount' => 0,
                'credit_limit' => 0,
                'max_limit' => null
            ],
            'settlement' => [
                'currency' => 'PHP',
                'amount' => 0,
                'credit_limit' => 0,
                'max_limit' => null
            ]
        ]
    ], [
        'name' => 'Gaudy',
        'role' => 'client',
        'metadata' => null,
        'wallets' => [
            'fund' => [
                'currency' => 'PHP',
                'amount' => 0,
                'credit_limit' => 0,
                'max_limit' => null
            ],
            'settlement' => [
                'currency' => 'PHP',
                'amount' => 0,
                'credit_limit' => 0,
                'max_limit' => null
            ]
        ]
    ], [
        'name' => 'LBCX North Hub',
        'role' => 'hub',
        'metadata' => [
            'areas' => [
                'pickup' => ['Valenzuela', 'Quezon City', 'Navotas', 'Marikina', 'Malabon', 'Caloocan'],
                'delivery' => ['Valenzuela', 'Quezon City', 'Navotas', 'Marikina', 'Malabon', 'Caloocan']
            ],
        ],
        'addresses' => [
            'business' => [
                'name' => 'LBCX North Hub',
                'line_1' => 'Tandang Sora',
                'city' => 'Quezon City',
                'state' => 'Manila',
                'postal_code' => '1123',
                'country' => 'PH',
            ],
        ],
        'relationships' => [
            'department_of' => 'LBCX'
        ]
    ], [
        'name' => 'LBCX South Hub',
        'role' => 'hub',
        'metadata' => [
            'areas' => [
                'pickup' => ['Taguig', 'San Juan', 'Pateros', 'Pasig', 'Pasay', 'Parañaque', 'Muntinlupa', 'Manila', 'Mandaluyong', 'Makati', 'Las Piñas'],
                'delivery' => ['Taguig', 'San Juan', 'Pateros', 'Pasig', 'Pasay', 'Parañaque', 'Muntinlupa', 'Manila', 'Mandaluyong', 'Makati', 'Las Piñas']
            ],
        ],
        'addresses' => [
            'business' => [
                'name' => 'LBCX South Hub',
                'line_1' => 'Yakal',
                'city' => 'Makati',
                'state' => 'Manila',
                'postal_code' => '1233',
                'country' => 'PH',
            ]
        ],
        'relationships' => [
            'department_of' => 'LBCX'
        ]
    ], [
        'name' => 'LBC Domestic Airport',
        'role' => 'hub',
        'metadata' => [
            'areas' => [
                'pickup' => '*',
                'delivery' => '*',
            ]
        ],
        'addresses' => [
            'business' => [
                'name' => 'LBC',
                'line_1' => 'Domestic Airport',
                'city' => 'Pasay',
                'state' => 'Manila',
                'postal_code' => '1301',
                'country' => 'PH',
            ],
        ],
        'relationships' => [
            'department_of' => 'LBC'
        ]
    ], [
        'name' => 'Stores Specialists Incorporated',
        'role' => 'client',
        'metadata' => null,
        'wallets' => [
            'fund' => [
                'currency' => 'PHP',
                'amount' => 0,
                'credit_limit' => 0,
                'max_limit' => null
            ],
            'settlement' => [
                'currency' => 'PHP',
                'amount' => 0,
                'credit_limit' => 0,
                'max_limit' => null
            ]
        ]
    ], [
        'name' => 'Adidas',
        'role' => 'client',
        'metadata' => null,
        'wallets' => [
            'fund' => [
                'currency' => 'PHP',
                'amount' => 0,
                'credit_limit' => 0,
                'max_limit' => null
            ],
            'settlement' => [
                'currency' => 'PHP',
                'amount' => 0,
                'credit_limit' => 0,
                'max_limit' => null
            ]
        ]
    ], [
        'name' => 'Go Easy Ship',
        'role' => 'client',
        'metadata' => null,
        'wallets' => [
            'fund' => [
                'currency' => 'PHP',
                'amount' => 0,
                'credit_limit' => 0,
                'max_limit' => null
            ],
            'settlement' => [
                'currency' => 'PHP',
                'amount' => 0,
                'credit_limit' => 0,
                'max_limit' => null
            ]
        ]
    ], [
        'name' => 'TackThis',
        'role' => 'client',
        'metadata' => null,
        'wallets' => [
            'fund' => [
                'currency' => 'PHP',
                'amount' => 0,
                'credit_limit' => 0,
                'max_limit' => null
            ],
            'settlement' => [
                'currency' => 'PHP',
                'amount' => 0,
                'credit_limit' => 0,
                'max_limit' => null
            ]
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
                DB::table('core.organizations')->updateOrInsert(['party_id' => $party_id], ['party_id' => $party_id, 'name' => $org['name']]);

                // Look for the role.
                $role_id = DB::table('core.roles')->where('name', $org['role'])->value('id');

                if (!$role_id) {
                    throw new \Exception('Role "' . $org['role'] . '" does not exsit.');
                }

                // Assign the role to the organization.
                DB::table('core.party_roles')->updateOrInsert(['party_id' => $party_id, 'role_id' => $role_id], [
                    'party_id' => $party_id,
                    'role_id' => $role_id
                ]);

                // Create the organization keys.
                $result = DB::table('core.api_keys')->where('party_id', $party_id)->get();

                if (!$result->toArray()) {
                    foreach (['dev', 'staging', 'production'] as $env) {
                        // Generate a key.
                        $key = __generate_api_key($party_id);

                        // Create the keys.
                        DB::table('core.api_keys')->insert(['party_id' => $party_id, 'name' => $env, 'api_key' => $key['api_key'], 'secret_key' => $key['secret_key'], 'created_at' => 'now()', 'expires_at' => $key['expires_at']]);
                    }
                }

                // Create the addresses.
                if (isset($org['addresses'])) {
                    foreach ($org['addresses'] as $type => $address) {
                        // Get the country ID.
                        $country_id = DB::table('core.locations')->where([['type', 'country'], ['code', $address['country']]])->value('id');

                        if (!$country_id) {
                            throw new \Exception('Country "' . $addresses['country'] . '"" does not exist.');
                        }

                        // Create the record.
                        unset($address['country']);
                        DB::table('core.addresses')->updateOrInsert(['party_id' => $party_id, 'type' => $type], array_merge(
                            $address, [
                                'hash' => Address::hash(array_merge($address, ['party_id' => $party_id])),
                                'type' => $type,
                                'party_id' => $party_id,
                                'country_id' => $country_id,
                            ]
                        ));
                    }
                }

                // Create the relationships.
                if (isset($org['relationships'])) {
                    foreach ($org['relationships'] as $type => $to_party) {
                        // Get the party ID.
                        $to_party_id = DB::table('core.organizations')->where('name', $to_party)->value('party_id');

                        // Create the relationship.
                        DB::table('core.relationships')->updateOrInsert(['from_party_id' => $party_id, 'type' => $type, 'to_party_id' => $to_party_id], ['from_party_id' => $party_id, 'type' => $type, 'to_party_id' => $to_party_id]);
                    }
                }

                // Create the wallets.
                if (isset($org['wallets'])) {
                    foreach ($org['wallets'] as $type => $wallet) {
                        // Get the currency ID.
                        $currency_id = DB::table('core.currencies')->where('code', $wallet['currency'])->value('id');

                        if (!$currency_id) {
                            throw new \Exception('Currency "' . $wallet['currency'] . '"" does not exist.');
                        }

                        // Check if the wallet exists.
                        $result = DB::table('wallet.wallets')->where([['party_id', $party_id], ['type', $type]])->first();

                        // Create the wallet if it doesn't exist.
                        if (!$result) {
                            unset($wallet['currency']);
                            DB::table('wallet.wallets')->insert(array_merge($wallet, [
                                'type' => $type,
                                'party_id' => $party_id,
                                'currency_id' => $currency_id,
                                'status' => 1,
                                'created_at' => DB::raw('now()')
                            ]));
                        }
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
