<?php
namespace F3\Models;

use DB;
use F3\Components\Model;

class Asset extends Model
{
    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'cms.assets';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = ['id', 'source', 'full_image', 'thumb_small', 'thumb_medium', 'thumb_large', 'filesize', 'mimetype', 'caption', 'content', 'created_by', 'created_at', 'modified_by', 'modified_at'];

    /**
     * The table's primary key.
     */
    protected $primaryKey = 'id';

    /**
     * Returns the model validation rules.
     */
    public function getRules()
    {
        return [
            'source' => 'url|nullable',
            'full_image' => 'url|nullable',
            'thumb_small' => 'url|nullable',
            'thumb_medium' => 'url|nullable',
            'thumb_large' => 'url|nullable',
            'filesize' => 'integer|nullable',
            'mimetype' => 'string|required',
            'caption' => 'string|nullable',
            'content' => 'string|nullable',
            'created_by' => 'string|nullable',
            'updated_by' => 'integer|nullable|exists:pgsql.core.users,party_id'
        ];
    }

    /**
     * Creates the asset.
     * @param string $mimetype Format of the file (example: image/jpeg)
     * @param string $source URL of the original/raw image/video asset
     * @param string $full_image URL of the optimized image file with the same dimensions as the source file
     * @param string $thumb_small URL of the small thumbnail
     * @param string $thumb_medium URL of the medium thumbnail
     * @param string $thumb_large URL of the large thumbnail
     * @param string $filesize Filesize in bytes
     * @param string $content Text / HTML content
     * @param string $caption Short text / title describing the asset
     */
    public static function store($mimetype, $source = null, $full_image = null, $thumb_small = null, $thumb_medium = null, $thumb_large = null, $filesize = null, $content = null, $caption = null)
    {
        try {
            // Build the attribute list.
            $attributes = [
                'mimetype' => $mimetype,
                'source' => $source,
                'full_image' => $full_image,
                'thumb_small' => $thumb_small,
                'thumb_medium' => $thumb_medium,
                'thumb_large' => $thumb_large,
                'filesize' => $filesize,
                'mimetype' => $mimetype,
                'caption' => $caption,
                'content' => $content,
            ];

            // Create the user. 
            return self::create($attributes);
        } catch (\Exception $e) {
            // Rollback and return the error.
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Creates a text asset.
     * @param string $content Text content
     * @param string $caption Short text / title describing the asset
     */
    public static function storeText($content, $caption = null)
    {
        return self::store('text/plain', null, null, null, null, null, null, $content, $caption);
    }

    /**
     * Creates an HTML asset.
     * @param string $content HTML content
     * @param string $caption Short text / title describing the asset
     */
    public static function storeHtml($content, $caption = null)
    {
        return self::store('text/html', null, null, null, null, null, null, $content, $caption);
    }

    /**
     * Creates an image asset.
     * @param string $mimetype Format of the file (example: image/jpeg)
     * @param string $source URL of the original/raw image/video asset
     * @param string $full_image URL of the optimized image file with the same dimensions as the source file
     * @param string $thumb_small URL of the small thumbnail
     * @param string $thumb_medium URL of the medium thumbnail
     * @param string $thumb_large URL of the large thumbnail
     * @param string $filesize Filesize in bytes
     * @param string $content Text / HTML content
     * @param string $caption Short text / title describing the asset
     */
    public static function storeImage($mimetype, $source, $full_image = null, $thumb_small = null, $thumb_medium = null, $thumb_large = null, $filesize = null, $content = null, $caption = null)
    {
        return self::store($mimetype, $source, $full_image, $thumb_small, $thumb_medium, $thumb_large, $filesize, $content, $caption);
    }
}
