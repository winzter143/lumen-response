<?php
namespace F3\Models;

use DB;
use F3\Components\Model;

class Wallet extends Model
{
    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'wallet.wallets';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = ['party_id', 'type', 'currency_id', 'max_limit', 'credit_limit'];

    /**
     * The table's primary key.
     */
    protected $primaryKey = 'id';

    /**
     * Wallet types.
     */
    const TYPES = ['fund', 'sales', 'settlement', 'collections'];
    
    /**
     * Returns the model validation rules.
     */
    public function getRules()
    {
        return [
                'party_id' => 'integer|required|exists:pgsql.core.parties,id',
                'type' => 'string|required|in:' . implode(',', self::TYPES),
                'currency_id' => 'integer|required|exists:pgsql.core.currencies,id',
                'max_limit' => 'numeric|nullable|min:0|max:999999999999.99',
                'credit_limit' => 'numeric|nullable|min:-999999999999.99|max:0',
                ];
    }

    /**
     * A wallet has wallet logs.
     */
    public function walletLogs()
    {
        return $this->hasMany('F3\Models\WalletLog');
    }
    
    /**
     * Get the wallet balance.
     *
     * @return int
     */
    public function getBalance()
    {
        return DB::table($this->table)->where($this->primaryKey, $this->id)->value('amount');
    }

    /**
     * Creates a wallet.
     * 
     * @param int    $party_id
     * @param string $type
     * @param string $currency
     * @param int    $max_limit
     * @param int    $credit_limit
     */
    public static function store($party_id, $type, $currency = 'PHP', $max_limit = 10000, $credit_limit = 0)
    {
        try {
            // Start the transaction.
            DB::beginTransaction();

            // Check the currency.
            $currency_id = DB::table('core.currencies')->where('code', $currency)->value('id');

            if (!$currency_id) {
                throw new \Exception('The selected currency code is invalid.', 422);
            }
            
            // The attributes to be saved.
            $attributes = [
                           'party_id' => $party_id,
                           'type' => $type,
                           'currency_id' => $currency_id,
                           'max_limit' => $max_limit,
                           'credit_limit' => $credit_limit
                           ];

            // Create the wallet.
            $wallet = self::create($attributes);

            // Commit and return the wallet.
            DB::commit();
            return $wallet;
        } catch (\Exception $e) {
            // Rollback and return the error.
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Transfers funds between wallets.
     *
     * @param int    $from_party_id
     * @param int    $to_party_id
     * @param string $from_type
     * @param string $to_type
     * @param string $currency
     * @param int    $amount
     * @param string $transfer_type
     * @param string $details
     * @param int    $order_id
     * @param string $ip_address
     */
    public static function transfer($from_party_id, $to_party_id, $from_type, $to_type, $currency, $amount, $transfer_type, $details, $order_id = null, $ip_address = null)
    {
        try {
            // Start the transaction.
            DB::beginTransaction();            

            // We only support 2 decimals for transfers.
            $amount = round($amount, 2);

            // Some basic sanity checks.
            if ($amount <= 0) {
                throw new \Exception('Transfer amount should be greater than 0.', 422);
            }

            // Check the currency.
            $currency_id = DB::table('core.currencies')->where('code', $currency)->value('id');

            if (!$currency_id) {
                throw new \Exception('The selected currency code is invalid.', 422);
            }
            
            // Locate and LOCK the source wallet.
            $from_wallet = DB::table('wallet.wallets AS w')
                         ->select('w.*')
                         ->join('core.parties AS p', 'w.party_id', 'p.id')
                         ->where([
                             ['w.party_id', $from_party_id],
                             ['w.type', $from_type],
                             ['w.currency_id', $currency_id],
                             ['w.status', 1],
                             ['p.status', 1],
                         ])
                         ->lockForUpdate()
                         ->first();

            if (!$from_wallet) {
                throw new \Exception('Cannot find source wallet.');
            }

            // Since Eloquent doesn't support lockForUpdate(), we load the model manually.
            $from_wallet = (new Wallet)->newFromBuilder($from_wallet);
            
            // Load the record into a model.  We can't do this directly since 
            // Locate and LOCK the destination wallet.
            $to_wallet = DB::table('wallet.wallets AS w')
                       ->select('w.*')
                       ->join('core.parties AS p', 'w.party_id', 'p.id')
                       ->where([
                           ['w.party_id', $to_party_id],
                           ['w.type', $to_type],
                           ['w.currency_id', $currency_id],
                           ['w.status', 1],
                           ['p.status', 1],
                       ])
                       ->lockForUpdate()
                       ->first();

            if (!$to_wallet) {
                throw new \Exception('Cannot find destination wallet.');
            }

            // Since Eloquent doesn't support lockForUpdate(), we load the model manually.
            $to_wallet = (new Wallet)->newFromBuilder($to_wallet);

            // Ensure that the source and destination wallets are different.
            if ($from_wallet->id == $to_wallet->id) {
                throw new \Exception('Source and destination wallets must be different.');
            }

            // Compute for the new source and destination amounts.
            $from_amount = $from_wallet->amount - $amount;
            $to_amount = $to_wallet->amount + $amount;

            // Check the credit limit of the source wallet.
            if (!is_null($from_wallet->credit_limit) && $from_amount < $from_wallet->credit_limit) {
                throw new \Exception('Insufficient funds for transfer.');
            }
        
            // Check the max limit of the destination wallet.
            if (!is_null($to_wallet->max_limit) && $to_amount > $to_wallet->max_limit) {
                throw new \Exception('Max limit exceeded for transfer.');
            }
            
            // Deduct from the source wallet.
            $from_wallet->amount = $from_amount;
            $from_wallet->save();

            // Credit the destination wallet.
            $to_wallet->amount = $to_amount;
            $to_wallet->save();
        
            // Log the transfer.
            $transfer = Transfer::store($from_wallet->id, $to_wallet->id, $transfer_type, $amount, $details, $order_id);

            // Log for each wallet.
            WalletLog::store($from_wallet->id, $transfer->id, -$amount, $from_wallet->amount);
            WalletLog::store($to_wallet->id, $transfer->id, $amount, $to_wallet->amount);
            
            // Commit if in our own transaction.
            DB::commit();
            return $transfer->id;
        } catch (\Exception $e) {
            // Rollback and return the error.
            DB::rollback();
            throw $e;
        }
    }
}
