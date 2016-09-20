<?php
namespace F3\Models;

use DB;
use F3\Components\Model;

class WalletLog extends Model
{
    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'wallet.wallet_logs';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = ['wallet_id', 'transfer_id', 'amount', 'running_balance'];

    /**
     * The table's primary key.
     */
    protected $primaryKey = 'id';

    /**
     * Do not update the timestamps.
     */
    public $timestamps = false;

    /**
     * Returns the model validation rules.
     */
    public function getRules()
    {
        return [
            'wallet_id' => 'integer|required|exists:pgsql.wallet.wallets,id',
            'transfer_id' => 'integer|required|exists:pgsql.wallet.transfers,id',
            'amount' => 'numeric|required|min:-999999999999.99|max:999999999999.99',
            'running_balance' => 'numeric|required|min:-999999999999.99|max:999999999999.99',
        ];
    }

    /**
     * Get the wallet.
     */
    public function wallet()
    {
        return $this->belongsTo('F3\Models\Wallet');
    }

    /**
     * Get the transfer.
     */
    public function transfer()
    {
        return $this->belongsTo('F3\Models\Transfer');
    }
    
    /**
     * Creates a wallet log entry.
     * 
     * @param int    $wallet_id
     * @param int    $transfer_id
     * @param int    $amount
     * @param int    $running_balance
     */
    public static function store($wallet_id, $transfer_id, $amount, $running_balance)
    {
        try {
            // Start the transaction.
            DB::beginTransaction();
            
            // The attributes to be saved.
            $attributes = [
                'wallet_id' => $wallet_id,
                'transfer_id' => $transfer_id,
                'amount' => $amount,
                'running_balance' => $running_balance,
            ];

            // Create the wallet.
            $wallet_log = self::create($attributes);

            // Commit and return the wallet.
            DB::commit();
            return $wallet_log;
        } catch (\Exception $e) {
            // Rollback and return the error.
            DB::rollback();
            throw $e;
        }
    }
}
