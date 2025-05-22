<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Collections;
use App\Models\Tags;

public function tags()
{
    return $this->belongsToMany(Tag::class, 'collection_tag', 'collection_id', 'tag_id');
}



