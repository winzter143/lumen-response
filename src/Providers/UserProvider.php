<?php
namespace F3\Providers;

use F3\Components\RBAC;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class UserProvider extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, RBAC {
        RBAC::can insteadof Authorizable;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'party_id', 'name', 'roles', 'scope', 'login_id'
    ];

    /**
     * Constructor.
     */
    public function __construct($params)
    {
        // Set the roles.
        $this->setRoles($params['roles']);
        $this->setScope($params['scope']);

        // Initialize the parent.
        parent::__construct($params);
    }

    /**
     * Returns the party name.
     */
    public function getName()
    {
        return $this->name;
    }
}
