<?php
namespace F3\components;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Validation\ValidationException;
use Validator;
use DB;

/**
 * Collection of helper functions.
 */
class Model extends EloquentModel
{
    /**
     * Unique constraint violation error code.
     */
    const PG_ERROR_UNIQUE_VIOLATION = 23505;

    /**
     * Constructor
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    /**
     * Validates the data using the rules above.
     */
    public function validate($attributes = [], $rules = [])
    {
        // Set the default attributes.
        if (!$attributes) {
            $attributes = $this->getAttributes();
        }

        // Set the default rules.
        if (!$rules) {
            $rules = $this->getRules();
        }

        // Create the validator.
        $validator = Validator::make($attributes, $rules);

        // Validate the data.
        if ($validator->passes()) {
            return true;
        } else {
            throw new ValidationException($validator);
        }
    }

    /**
     * Save the model to the DB.
     */
    public function save(array $options = [])
    {
        // Validate the data before saving.
        $this->validate();

        // Save the data.
        return parent::save($options);
    }

    /**
     * Checks if the given error code is a unique key violation error.
     */
    public static function isUniqueViolationError($error_code)
    {
        return (self::PG_ERROR_UNIQUE_VIOLATION == $error_code);
    }
}
