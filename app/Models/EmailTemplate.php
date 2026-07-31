<?php

namespace App\Models;

use App\Exceptions\MissingEmailTemplateException;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'slug',
        'label',
        'subject',
        'body',
        'description',
    ];

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    public static function requireBySlug(string $slug): self
    {
        $template = static::findBySlug($slug);

        if ($template === null) {
            throw MissingEmailTemplateException::forSlug($slug);
        }

        if (! filled($template->subject) || ! filled($template->body)) {
            throw MissingEmailTemplateException::incomplete($slug);
        }

        return $template;
    }
}
