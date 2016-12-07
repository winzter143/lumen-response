<?php
namespace F3\Models;

use DB;
use F3\Components\Model;

class Address extends Model
{
    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'core.addresses';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = ['party_id', 'type', 'name', 'title', 'email', 'phone_number', 'mobile_number', 'fax_number', 'company', 'line_1', 'line_2', 'city', 'state', 'postal_code', 'country_id', 'remarks', 'created_by', 'hash'];

    /**
     * Returns the model validation rules.
     */
    public function getRules()
    {
        return [
            'party_id' => 'integer|required|exists:pgsql.core.parties,id',
            'type' => 'string|in:pickup,delivery,warehouse',
            'name' => 'string|required|max:255',
            'title' => 'string|nullable|max:50',
            'email' => 'string|nullable|max:50',
            'phone_number' => 'string|nullable|max:50',
            'mobile_number' => 'string|nullable|max:50',
            'fax_number' => 'string|nullable|max:50',
            'company' => 'string|nullable|max:255',
            'line_1' => 'string|required',
            'line_2' => 'string|nullable',
            'city' => 'string|required',
            'state' => 'string|required',
            'postal_code' => 'string|required|max:50',
            'country_id' => 'integer|required|exists:pgsql.core.locations,id',
            'remarks' => 'string|nullable',
            'created_by' => 'integer|nullable|exists:pgsql.core.parties,id',
            'hash' => 'string|required|min:32|max:32'
        ];
    }

    /**
     * An address is tied to an order.
     */
    public function order()
    {
        return $this->belongsTo('F3\Models\Order');
    }

    /**
     * An address has a country.
     */
    public function country()
    {
        return $this->hasOne('F3\Models\Location', 'id', 'country_id');
    }

    /**
     * Creates a new address.
     */
    public static function store($party_id, $type, $name, $line_1, $line_2 = null, $city, $state, $postal_code, $country_code, $remarks = null, $created_by = null, $title = null, $email = null, $phone_number = null, $mobile_number = null, $fax_number = null, $company = null)
    {
        // Look for the country ID.
        $country_id = DB::table('core.locations')->where([['type', 'country'], ['code', $country_code]])->value('id');

        if (!$country_id) {
            throw new \Exception("The provided country code for the {$type} address is not valid.", 422);
        }

        // Build the attribute list.
        $attributes = [
            'party_id' => $party_id,
            'type' => $type,
            'name' => $name,
            'title' => $title,
            'email' => $email,
            'phone_number' => $phone_number,
            'mobile_number' => $mobile_number,
            'fax_number' => $fax_number,
            'company' => $company,
            'line_1' => $line_1,
            'line_2' => $line_2,
            'city' => $city,
            'state' => $state,
            'postal_code' => $postal_code,
            'country_id' => $country_id,
            'remarks' => $remarks,
            'created_by' => $created_by
        ];

        // Hash the address.
        $attributes['hash'] = self::hash($attributes);

        // Check if the address exists.
        $address = self::where(['party_id' => $party_id, 'hash' => $attributes['hash']])->first();

        if ($address) {
            // Update the fields that are not included in the address hash.
            $address->update(['title' => $attributes['title'], 'fax_number' => $attributes['fax_number'], 'remarks' => $attributes['remarks']]);
            return $address;
        } else {
            return self::create($attributes);
        }
    }

    /**
     * Updates the address.
     */
    public function updateAddress($type, $name, $line_1, $line_2, $city, $state, $postal_code, $country_code, $remarks, $title, $email, $phone_number, $mobile_number, $fax_number, $company)
    {
        // Look for the country ID.
        $country_id = DB::table('core.locations')->where([['type', 'country'], ['code', $country_code]])->value('id');

        if (!$country_id) {
            throw new \Exception("The provided country code for the {$type} address is not valid.", 422);
        }

        // Build the attribute list.
        $attributes = [
            'party_id' => $this->party_id,
            'type' => $type,
            'name' => $name,
            'title' => $title,
            'email' => $email,
            'phone_number' => $phone_number,
            'mobile_number' => $mobile_number,
            'fax_number' => $fax_number,
            'company' => $company,
            'line_1' => $line_1,
            'line_2' => $line_2,
            'city' => $city,
            'state' => $state,
            'postal_code' => $postal_code,
            'country_id' => $country_id,
            'remarks' => $remarks,
            'created_by' => $this->created_by
        ];

        // Hash the address.
        $attributes['hash'] = self::hash($attributes);

        // Update the address.
        return $this->update($attributes);
    }

    /**
     * Hashes the address.
     */
    public static function hash($address)
    {
        return md5(trim(array_get($address, 'party_id')) . '|' . trim(array_get($address, 'type')) . '|' . trim(array_get($address, 'name')) . '|' . trim(array_get($address, 'email')) . '|' . trim(array_get($address, 'phone_number')) . '|' . trim(array_get($address, 'mobile_number')) . '|' . trim(array_get($address, 'company')) . '|' . trim(array_get($address, 'name')) . '|' . trim(array_get($address, 'line_1')) . '|' . trim(array_get($address, 'line_2')) . '|' . trim(array_get($address, 'city')) . '|' . trim(array_get($address, 'state')) . '|' . trim(array_get($address, 'postal_code')) . '|' . trim(array_get($address, 'country_id')));
    }

    /**
     * Formats the address array and converts it to string.
     */
    public static function format(array $address, $delimeter = '\n')
    {
        // Get the country.
        $country = array_get($address, 'country');

        if (is_array($country)) {
            $country = array_get($country, 'code');
        }

        return trim(str_replace('  ', ' ', implode($delimeter, [
            array_get($address, 'line_1'),
            array_get($address, 'line_2'),
            array_get($address, 'city'),
            array_get($address, 'state'),
            array_get($address, 'postal_code'),
            array_get($address, $country)
        ])));
    }

    /**
     * Checks if the given address is provincial.
     */
    public static function isProvincial(array $address)
    {
        // Format the address.
        $address = self::format($address, ', ');

        // Fetch the local delivery areas.
        $areas = config('settings.local_areas');

        // Check if the address is a provincial address.
        foreach ($areas as $area) {
            if (strpos(strtolower($address), strtolower($area)) !== false) {
                return false;
            }
        }

        return true;
    }
}
