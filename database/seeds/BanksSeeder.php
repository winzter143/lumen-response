<?php
use Illuminate\Database\Seeder;

class BanksSeeder extends Seeder
{
    /**
     * List of banks.
     */
    private $banks = [
        [
            'name' => 'ANZ',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'Al-Amanah Islamic Investment Bank of the Philippines',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'Asia United Bank (AUB)',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'BDO Private Bank (subsidiary of BDO Unibank)',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'BDO Unibank (BDO)',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'Bangkok Bank',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'Bank of America, N.A.',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'Bank of China Manila Branch',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'Bank of Commerce',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'Bank of the Philippine Islands (BPI)',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'CTBC Bank (Chinatrust)',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'Chinabank',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'Citibank',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'Deutsche Bank',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'Development Bank of the Philippines (DBP)',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'EastWest Unibank',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'ING Group N.V.',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'JPMorgan Chase',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'Korea Exchange Bank',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'Land Bank of the Philippines (LBP)',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'Maybank Philippines, Inc.',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'Mega International Commercial Bank',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'Metropolitan Bank and Trust Company (Metrobank)',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'Mizuho Bank, Ltd. Manila Branch',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'Philippine Bank of Communications (PBCom)',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'Philippine National Bank (PNB)',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'Philippine Veterans Bank (Veterans Bank; PVB)',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'Philtrust Bank',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'Rizal Commercial Banking Corporation (RCBC)',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'Robinsons Bank',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'Security Bank',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'Standard Chartered Bank',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'The Bank of Tokyo-Mitsubishi UFJ, Ltd.',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'The Hongkong and Shanghai Banking Corporation (HSBC)',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'Union Bank of the Philippines (Unionbank)',
            'swift_code' => null,
            'phone_number' => null
        ], [
            'name' => 'United Coconut Planters Bank (UCPB)',
            'swift_code' => null,
            'phone_number' => null
        ], ];

    /**
     * Execute the console command.
     * @return void
     */
    public function run()
    {
        try {
            // Start the transaction.
            DB::beginTransaction();

            // Insert the records.
            foreach ($this->banks as $bank) {
                DB::table('core.banks')->updateOrInsert(['name' => $bank['name']], array_merge($bank, ['created_at' => 'now()']));
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
