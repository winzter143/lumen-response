<?php
namespace F3\Models;

use DB;
use F3\Components\Model;

class Ledger extends Model
{
    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'wallet.ledger';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = ['party_id', 'settlement_transfer_id', 'type', 'status', 'amount', 'breakdown', 'reference_id', 'bank_details', 'remarks', 'period', 'closed_at', 'settled_at', 'settled_by', 'created_at'];

    /**
     * The table's primary key.
     */
    protected $primaryKey = 'id';

    /**
     * Turn off timestamps. This will prevent Laravel from updating created_at and updated_at.
     */
    public $timestamps = false;

    /**
     * Returns the model validation rules.
     */
    public function getRules()
    {
        // Set the validation rules.
        $rules = [
            'party_id' => 'integer|required|exists:pgsql.core.organizations,party_id',
            'settlement_transfer_id' => 'integer|nullable|exists:pgsql.wallet.transfers,id',
            'type' => 'string|required|in:' . implode(',', array_keys(config('settings.ledger_entry_types'))),
            'status' => 'string|required|in:' . implode(',', array_keys(config('settings.ledger_entry_statuses'))),
            'amount' => 'numeric|required|min:0|max:999999999999.99',
            'breakdown' => 'json|required',
            'reference_id' => 'string|nullable|max:100',
            'bank_details' => 'json|nullable',
            'remarks' => 'string|nullable',
            'period' => 'string|required',
            'closed_at' => 'date|required',
            'settled_at' => 'date|nullable',
            'settled_by' => 'integer|nullable',
        ];

        return $rules;
    }

    /**
     * Creates a new ledger entry.
     * @param int $party_id
     * @param string $type payable | receivable
     * @param float $amount
     * @param array $breakdown
     * @param string $closed_at
     * @param string $started_at
     * @param string $ended_at
     * @param array $transfers Array of transfer IDs
     * @param string $status pending | settled
     * @param string $reference_id
     * @param array $bank_details
     * @param string $remarks
     */
    public static function store($party_id, $type, $amount, $breakdown, $closed_at, $started_at, $ended_at, $transfers, $status = 'pending', $reference_id = null, $bank_details = null, $remarks = null)
    {
        try {
            // Start the transaction.
            DB::beginTransaction();

            // Create the timestamp date range (inclusive []).
            $period = json_encode([$started_at, $ended_at]);

            // Build the list of attributes to be saved.
            $attributes = [
                'party_id' => $party_id,
                'type' => $type,
                'status' => $status,
                'amount' => $amount,
                'breakdown' => json_encode($breakdown),
                'reference_id' => $reference_id,
                'bank_details' => json_encode($bank_details),
                'remarks' => $remarks,
                'closed_at' => $closed_at,
                'period' => $period,
            ];

            // Create the entry.
            $result = self::create($attributes);

            // Update the transfers.
            DB::table('wallet.transfers')->whereIn('id', $transfers)->update(['ledger_id' => $result->id]);

            // Update the party.
            DB::table('core.parties')->where('id', $party_id)->update(['last_disbursed_at' => DB::raw('now()')]);

            // Commit and return the entry.
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            // Rollback and return the error.
            DB::rollback();
            throw $e;
        }
    }
}
