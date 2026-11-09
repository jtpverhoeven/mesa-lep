<?php

namespace App\Models;

use Database\Factories\EmailTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'id', 'name', 'template', 'template_en', 'bcc',
    'defaultmail', 'defaultmail_with_files', 'defaultmail_for_files',
])]
class EmailTemplate extends Model
{
    /** @use HasFactory<EmailTemplateFactory> */
    use HasFactory;

    protected $table = 'emailtemplates';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'defaultmail' => 'integer',
            'defaultmail_with_files' => 'integer',
            'defaultmail_for_files' => 'integer',
        ];
    }
}
