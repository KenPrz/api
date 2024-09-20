<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFollower extends Model
{
    use HasFactory;

    protected $table = 'user_follower';

    protected $fillable = [
        'user_id',
        'follower_id',
    ];

    /**
     * Get the user that owns the follower.
     *
     * This function defines an inverse one-to-many relationship
     * between the UserFollower model and the User model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user that is following.
     *
     * This method defines a relationship where the current model instance
     * belongs to a user who is the follower. It uses the 'follower_id'
     * foreign key to establish the connection.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    /**
     * Get the followers for the user.
     *
     * This function defines a one-to-many relationship between the User model
     * and the UserFollower model, where the foreign key is 'user_id'.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function followers()
    {
        return $this->hasMany(UserFollower::class, 'user_id');
    }

    /**
     * Scope a query to fetch followers of a given user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $user_id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFetchFollowers($query, $user_id)
    {
        return $query->where('user_id', $user_id)
            ->where('users.deleted_at', null)
            ->join('users', 'users.id', '=', 'user_follower.follower_id');
    }
}
