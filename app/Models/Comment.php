<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Comment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'post_id',
        'content',
        'is_edited',
    ];

    protected $casts = [
        'is_edited' => 'boolean',
    ];

    /**
     * Get the user that owns the comment.
     *
     * This function defines an inverse one-to-many relationship
     * between the Comment model and the User model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the user that owns the comment.
     *
     * defines an inverse one-to-many relationship
     * between the Comment model and the User model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determines if a user can post a comment based on their comment count.
     *
     * This method checks the number of comments a user has made within a certain
     * time frame (5 minutes). If the user has made 20 or more comments, they are
     * not allowed to post another comment until the time frame resets.
     *
     * @param  int  $userId  The ID of the user attempting to comment.
     * @return bool Returns true if the user can comment, false otherwise.
     */
    public static function canUserComment($userId)
    {
        // Cache key for the user's comment count
        $key = "user_comment_count_{$userId}";
        // Get the current comment count for the user
        $commentCount = Cache::get($key, 0);

        // If the user has made 20 or more comments within 5 minutes, they are not allowed to comment
        if ($commentCount >= 20) {
            return false;
        }
        // Increment the comment count and store it in the cache for 5 minutes
        Cache::put($key, $commentCount + 1, now()->addMinutes(5));

        return true;
    }

    /**
     * Scope a query to fetch the latest comments for a specific post.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  The query builder instance.
     * @param  array  $value  An associative array containing:
     *                        - 'post_id' (int): The ID of the post.
     *                        - 'created_at' (string): The timestamp to compare against.
     * @return \Illuminate\Database\Eloquent\Builder The modified query builder instance.
     */
    public function scopeFetchLatest($query, array $value): mixed
    {
        return $query->where('post_id', $value['post_id'])
            ->where('created_at', '>', $value['created_at'])
            ->orderBy('created_at', 'desc');
    }
}
