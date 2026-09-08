<?php

namespace App\Actions\ClientCategories;

use App\Models\ClientCategory;
use Illuminate\Support\Facades\DB;

class DeleteClientCategory
{
    public function handle(ClientCategory $category): void
    {
        DB::transaction(function () use ($category) {
            $category->clients()->detach();
            $category->delete();
        });
    }
}