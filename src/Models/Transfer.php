<?php
namespace F3\Models;

use DB;
use F3\Components\Model;

class Transfer extends Model
{
    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'wallet.transfers';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = ['from_wallet_id', 'to_wallet_id', 'type', 'amount', 'order_id', 'details', 'ip_address'];

    /**
     * The table's primary key.
     */
    protected $primaryKey = 'id';

    /**
     * Do not update the timestamps.
     */
    public $timestamps = false;

    /**
     * Wallet types.
     */
    private const TYPES = ['purchase', 'transfer', 'refund', 'reward', 'escrow', 'disbursement', 'settlement'];

    /**
     * Returns the model validation rules.
     */
    public function getRules()
    {
        return [
            'from_wallet_id' => 'integer|required|exists:pgsql.wallet.wallets,id',
            'to_wallet_id' => 'integer|required|exists:pgsql.wallet.wallets,id',
            'type' => 'string|required|in:' . implode(',', self::TYPES),
            'amount' => 'numeric|required|min:0|max:999999999999.99',
            'details' => 'string|required',
            'order_id' => 'integer|nullable|exists:pgsql.consumer.orders,id',
            'ip_address' => 'ip|nullable',
        ];
    }

    /**
     * A transfer has wallet logs.
     */
    public function walletLogs()
    {
        return $this->hasMany('F3\Models\WalletLog');
    }
    
    /**
     * Creates a transfer.
     * 
     * @param int    $from_wallet_id
     * @param int    $to_wallet_id
     * @param string $type
     * @param int    $amount
     * @param string $details
     * @param int    $order_id
     */
    public static function store($from_wallet_id, $to_wallet_id, $type, $amount, $details, $order_id = null, $ip_address = null)
    {
        try {
            // Start the transaction.
            DB::beginTransaction();
            
            // The attributes to be saved.
            $attributes = [
                'from_wallet_id' => $from_wallet_id,
                'to_wallet_id' => $to_wallet_id,
                'type' => $type,
                'amount' => $amount,
                'details' => $details,
                'order_id' => $order_id,
                'ip_address' => $ip_address,
            ];

            // Create the wallet.
            $transfer = self::create($attributes);

            // Commit and return the wallet.
            DB::commit();
            return $transfer;
        } catch (\Exception $e) {
            // Rollback and return the error.
            DB::rollback();
            throw $e;
        }
    }
}
