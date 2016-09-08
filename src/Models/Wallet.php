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
    protected $fillable = [];

    /**
     * The table's primary key.
     */
    protected $primaryKey = 'id';

    /**
     * Returns the model validation rules.
     */
    public function getRules()
    {
        return [];
    }

    /**
     * Transfers funds between wallets.
     */
    public static function transfer($from_party_id, $to_party_id, $from_type, $to_type, $currency_id, $amount, $transfer_type, $details, $order_id = null)
    {
        // Some basic sanity checks.
        if ($amount <= 0) {
            throw new Exception('Amount should be greater than 0');
        } else {
            // We only support 2 decimals for transfers.
            $amount = round($amount, 2);
        }
        
        try {
            // Find out if we're in a transaction, if not, start one.
            if (!Yii::$app->db->getTransaction()) {
                $txn = Yii::$app->db->beginTransaction();
            }

            // Locate the source wallet.
            $src_wallet = Wallet::findBySql('SELECT w.* FROM wallet.wallets w JOIN core.parties p on w.party_id = p.id WHERE w.party_id = :from_party_id AND w.type = :from_type AND w.currency_id = :currency_id AND p.status = :party_status and w.status = :wallet_status FOR UPDATE',
                                            [':from_party_id' => $from_party_id, ':from_type' => $from_type, ':currency_id' => $currency_id, ':party_status' => 1, ':wallet_status' => 1])->one();

            if (!$src_wallet) {
                throw new Exception('Cannot find source wallet');
            }
            
            // Locate the destination wallet.
            $dst_wallet = Wallet::findBySql('SELECT w.* FROM wallet.wallets w JOIN core.parties p on w.party_id = p.id WHERE w.party_id = :to_party_id AND w.type = :to_type AND w.currency_id = :currency_id AND p.status = :party_status and w.status = :wallet_status FOR UPDATE',
                                            [':to_party_id' => $to_party_id, ':to_type' => $to_type, ':currency_id' => $currency_id, ':party_status' => 1, ':wallet_status' => 1])->one();

            if (!$dst_wallet) {
                throw new Exception('Cannot find destination wallet');
            }

            // Ensure that the source and destination wallets are different.
            if ($src_wallet->id == $dst_wallet->id) {
                throw new Exception('Source and destination wallets need to be different');
            }

            // Compute for the new source and destination amounts.
            $src_amount = $src_wallet->amount - $amount;
            $dst_amount = $dst_wallet->amount + $amount;

            // Check the credit limit of the source wallet.
            if (!is_null($src_wallet->credit_limit) && $src_amount < $src_wallet->credit_limit) {
                throw new Exception('Credit limit exceeded');
            }
        
            // Check the max limit of the destination wallet.
            if (!is_null($dst_wallet->max_limit) && $dst_amount > $dst_wallet->max_limit) {
                throw new Exception('Max limit exceeded');
            }
            
            // Deduct from the source wallet.
            $src_wallet->amount = $src_amount;
            $result = $src_wallet->save();

            // Check for errors
            if (!$result) {
                throw new Exception($src_wallet);
            }

            // Credit the destination wallet.
            $dst_wallet->amount = $dst_amount;
            $result = $dst_wallet->save();
        
            // Check for errors
            if (!$result) {
                throw new Exception($dst_wallet);
            }

            // Log the transfer.
            $transfer = Transfer::create($src_wallet->id, $dst_wallet->id, $transfer_type, $amount, $details, $order_id);

            // Log for each wallet.
            WalletLog::create($src_wallet->id, $transfer->id, -$amount, $src_wallet->amount);
            WalletLog::create($dst_wallet->id, $transfer->id, $amount, $dst_wallet->amount);
            
            // Commit if in our own transaction.
            if (isset($txn)) {
                $txn->commit();
            }

            return $transfer->id;
            
        } catch (Exception $e) {
            // Rollback and return the error.
            // We still need to check for the existence of a transaction in case we are in a nested transaction and
            // we have been rolled back by a function we called.
            if (Yii::$app->db->getTransaction()) {
                Yii::$app->db->getTransaction()->rollback();
            }
            throw $e;
        }
    }
}
