<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Post extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'theme_id',
        'cover_image',
        'is_public',
        'user_id',
        'is_shared',
        'shared_post_id',
        'plain_text',
    ];

    protected $casts = [
        'plain_text' => 'string',
        'is_shared' => 'boolean',
        'is_public' => 'boolean',
    ];

    protected $hidden = [
        'plain_text',
    ];

    protected $appends = ['is_liked_by_user'];

    /**
     * The `boot` method is called when the `Post` model is being booted.
     * It registers an event listener for the `saving` event, which is triggered
     * when a `Post` model is being saved. The event listener sets the `plain_text`
     * attribute of the `Post` model by stripping HTML tags from the `content` attribute.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($post) {
            $post->plain_text = $post->strip_tags_content(strip_tags($post->content));
        });
    }

    /**
     * Get the user that owns the post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the comments for the post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Define a many-to-many relationship between the Post model and the User model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function likedByUsers()
    {
        return $this->belongsToMany(User::class, 'likes')->withTimestamps();
    }

    /**
     * Check if the post is liked by the currently authenticated user.
     *
     * @return bool
     */
    public function getIsLikedByUserAttribute($user_id)
    {
        return $this->likedByUsers->contains($user_id);
    }

    /**
     * Get the original post that this post belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function originalPost()
    {
        return $this->belongsTo(Post::class, 'shared_post_id');
    }

    /**
     * Define a relationship between the Post model and its shared posts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function shares()
    {
        return $this->hasMany(Post::class, 'shared_post_id');
    }

    /**
     * Get the theme that the post belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function theme()
    {
        return $this->belongsTo(Theme::class, 'theme_id');
    }

    /**
     * Scope to eager load relations and get counts for comments and likes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $userId The ID of the user to fetch posts for.
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithRelationsAndCounts($query, $userId)
    {

        return $query->with([
            'user',
            'theme' => function ($query) {
                $query->select('id', 'name');
            },
            'likedByUsers' => function ($query) {
                $query->select(
                    'users.id',
                    'users.first_name',
                    'users.last_name',
                    'users.avatar',
                    'users.handle'
                );
            },
            'originalPost' => function ($query) {
                $query->select(
                    'id',
                    'title',
                    'content',
                    'theme_id',
                    'cover_image',
                    'is_public',
                    'user_id',
                    'is_shared',
                    'created_at',
                    'updated_at'
                )->with([
                    'user' => function ($query) {
                        $query->select('id', 'handle', 'avatar');
                    },
                    'theme' => function ($query) {
                        $query->select('id', 'name');
                    },
                ]);
            },
            'shares' => function ($query) {
                $query->select(
                    'id',
                    'title',
                    'content',
                    'theme_id',
                    'cover_image',
                    'is_public',
                    'user_id',
                    'is_shared',
                    'shared_post_id'
                )->with([
                    'theme' => function ($query) {
                        $query->select('id', 'name');
                    },
                    'user' => function ($query) {
                        $query->select('id', 'first_name', 'last_name', 'avatar', 'handle');
                    },
                ]);
            },
        ])->where(function ($query) use ($userId) {
            $query->where('is_public', true)
                ->orWhere(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                        ->orWhereExists(function ($query) use ($userId) {
                            $query->select(DB::raw(1))
                                ->from('user_follower as uf1')
                                ->join('user_follower as uf2', function ($join) {
                                    $join->on('uf1.user_id', '=', 'uf2.follower_id')
                                        ->on('uf1.follower_id', '=', 'uf2.user_id');
                                })
                                ->whereColumn('posts.user_id', 'uf1.user_id')
                                ->where('uf1.follower_id', $userId);
                        });
                });
        })
            ->withCount(['comments', 'likedByUsers'])
            ->latest('created_at');
    }

    /**
     * Scope a query to include necessary fields for displaying posts.
     *
     * This scope selects specific columns from the posts table and joins
     * with the themes and users tables to include additional related data.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchDisplay($query)
    {
        return $query->select(
            'posts.id',
            'posts.title',
            'posts.content',
            'posts.cover_image',
            'posts.created_at',
            'users.handle',
            'themes.name as theme_name'
        )
            ->join('themes', 'posts.theme_id', '=', 'themes.id')
            ->join('users', 'posts.user_id', '=', 'users.id');
    }

    /**
     * Scope a query to search posts by title, content, theme name, or user handle.
     *
     * This scope performs a case-insensitive search on the following fields:
     * - `posts.title`
     * - `posts.content` (with HTML tags removed)
     * - `themes.name`
     * - `users.handle`
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  The query builder instance.
     * @param  string  $value  The search term to filter the posts.
     * @return \Illuminate\Database\Eloquent\Builder The modified query builder instance.
     */
    public function scopeSearch($query, $value)
    {
        return $query->where(function ($query) use ($value) {
            $query->whereRaw('LOWER(posts.title) LIKE ?', ['%'.strtolower($value).'%'])
                ->orWhereRaw("LOWER(REGEXP_REPLACE(posts.content, '<[^>]+>', ' ')) LIKE ?", ['%'.strtolower($value).'%'])
                ->orWhereRaw('LOWER(themes.name) LIKE ?', ['%'.strtolower($value).'%'])
                ->orWhereRaw('LOWER(users.handle) LIKE ?', ['%'.strtolower($value).'%']);
        });
    }

    /**
     * Scope a query to only include posts that are accessible to a specific user.
     *
     * This scope filters posts based on the following conditions:
     * - The post is public.
     * - The post belongs to the specified user.
     * - The post belongs to a user who is followed by the specified user, and the specified user is followed back by that user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  The query builder instance.
     * @param  int  $userId  The ID of the user to check accessibility for.
     * @return \Illuminate\Database\Eloquent\Builder The modified query builder instance.
     */
    public function scopeUserAccessible($query, $userId)
    {
        return $query->where(function ($query) use ($userId) {
            $query->where('is_public', true)
                ->orWhere('user_id', $userId)
                ->orWhereExists(function ($query) use ($userId) {
                    $query->select(DB::raw(1))
                        ->from('user_follower as uf1')
                        ->join('user_follower as uf2', function ($join) {
                            $join->on('uf1.user_id', '=', 'uf2.follower_id')
                                ->on('uf1.follower_id', '=', 'uf2.user_id');
                        })
                        ->whereColumn('posts.user_id', 'uf1.user_id')
                        ->where('uf1.follower_id', $userId);
                });
        });
    }

    /**
     * Strip HTML tags from the given text.
     *
     * @param  string  $text  The text to strip HTML tags from.
     * @return string The text with HTML tags removed.
     */
    private function strip_tags_content($text)
    {
        return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
    }

    /**
     * Scope a query to fetch public posts or posts by a specific user.
     *
     * This scope method modifies the query to include posts that are either public
     * or belong to the specified user. It also includes related models,
     * and orders the results by the latest creation date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  The query builder instance.
     * @param  int  $user_id  The ID of the user whose posts should be included.
     * @return \Illuminate\Database\Eloquent\Builder The modified query builder instance.
     */
    public function scopeFetchPublicPosts($query, $user_id)
    {
        return $query->where('is_public', true)
            ->orWhere('user_id')
            ->withRelationsAndCounts()
            ->latest('created_at');
    }

    /**
     * Scope a query to fetch all posts from specified followers or public posts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $followerIds  Array of follower user IDs.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFetchAllPosts($query, $followerIds)
    {
        return $query->whereIn('user_id', $followerIds)
            ->orWhere('is_public', true)
            ->withRelationsAndCounts();
    }
}
