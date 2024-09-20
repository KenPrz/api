<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'handle',
        'first_name',
        'last_name',
        'birthday',
        'avatar',
        'lot_block_no',
        'street',
        'city',
        'province',
        'country',
        'zip_code',
        'email',
        'phone_no',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];


    /**
     * Get the attributes that should be hidden for arrays.
     *
     * This method returns an array of attribute names that should be hidden
     * when the model is converted to an array or JSON. Typically, sensitive
     * information such as passwords and tokens are included in this list.
     *
     * @return array The list of attributes to be hidden.
     */
    protected function hidden(): array
    {
        return [
            'password',
            'remember_token',
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // This will automatically hash the password before storing it in the database
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }

    /**
     * Define a belongsTo relationship with the post model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Post,\App\Models\User>
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Get the posts that the user has liked.
     *
     * This function defines a many-to-many relationship between the User and Post models
     * using the 'likes' pivot table. It also ensures that the timestamps for the pivot
     * table are maintained.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function likedPosts()
    {
        return $this->belongsToMany(Post::class, 'likes')->withTimestamps();
    }

    /**
     * Get the users that follow the current user.
     *
     * This method defines a many-to-many relationship between users,
     * where the pivot table 'user_follower' is used to store the follower
     * relationships.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function followers()
    {
        return $this->belongsToMany(User::class, 'user_follower', 'follower_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Get the users that this user is following.
     *
     * This relationship uses a pivot table 'user_follower' with the following columns:
     * - user_id: The ID of the user who is following.
     * - follower_id: The ID of the user being followed.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function following()
    {
        return $this->belongsToMany(User::class, 'user_follower', 'user_id', 'follower_id')
            ->withTimestamps();
    }

    /**
     * Check if the user is followed by the currently authenticated user.
     *
     * This method checks if the authenticated user is in the list of followers
     * of the current user instance.
     *
     * @return bool True if the authenticated user is a follower, false otherwise.
     */
    public function isFollowedByMe($user_id)
    {
        return $this->followers->contains('id', $user_id);
    }

    /**
     * Check if the user follows another user.
     *
     * @param  int  $user_id  The ID of the user to check if the current user follows.
     * @return bool Returns true if the user follows the specified user, otherwise false.
     */
    public function isFollowing($user_id)
    {
        return $this->following()->where('user_id', $user_id)
            ->exists();
    }

    /**
     * Check if the user is followed by another user.
     *
     * @param  int  $user_id  The ID of the user to check if they are following.
     * @return bool Returns true if the user is followed by the specified user, otherwise false.
     */
    public function isFollowed($user_id)
    {
        return $this->followers()->where('follower_id', $user_id)->exists();
    }

    /**
     * Scope query for the search display of users
     *
     * @param  \Illuminate\Database\Eloquent\Builder<User>  $query
     * @param  string  $value
     * @return \Illuminate\Database\Eloquent\Builder<User>
     */
    public function scopeSearchDisplay($query, $value)
    {
        return $query->select('id', 'first_name', 'last_name', 'handle', 'avatar', 'created_at')
            ->search($value);
    }

    /**
     * Scope query for searching users
     *
     * @param  \Illuminate\Database\Eloquent\Builder<User>  $query
     * @param  string  $value
     * @return \Illuminate\Database\Eloquent\Builder<User>
     */
    public function scopeSearch($query, $value)
    {
        return $query->where(function ($subQuery) use ($value) {
            $subQuery->whereRaw('LOWER(first_name) LIKE ?', ['%'.strtolower($value).'%'])
                ->orWhereRaw('LOWER(last_name) LIKE ?', ['%'.strtolower($value).'%'])
                ->orWhereRaw('LOWER(handle) LIKE ?', ['%'.strtolower($value).'%'])
                ->orWhereRaw("CONCAT(LOWER(first_name), ' ', LOWER(last_name)) LIKE ?", ['%'.strtolower($value).'%']);
        });
    }

    /**
     * Scope a query to only include recommended followers.
     *
     * This scope selects the 'id', 'first_name', 'last_name', 'avatar', and 'handle'
     * columns from the users table.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDisplayRecommendedFollowers($query)
    {
        return $query->select('id', 'first_name', 'last_name', 'avatar', 'handle');
    }

    /**
     * Scope a query to fetch followers of a user.
     *
     * This scope filters out users who are already following the given user
     * and excludes the user themselves from the result set. The result is
     * limited by a configurable search limit.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  The query builder instance.
     * @param  int  $user_id  The ID of the user whose followers are to be fetched.
     * @return \Illuminate\Database\Eloquent\Builder The modified query builder instance.
     */
    public function scopeFetchFollowers($query, $user_id)
    {
        return $query->whereNotIn('id', function ($query) use ($user_id) {
            $query->select('follower_id')
                ->from('user_follower')
                ->where('user_id', $user_id);
            })
            ->where('id', '!=', $user_id)
            ->limit(config('constants.search_limit.default'));
    }


}
