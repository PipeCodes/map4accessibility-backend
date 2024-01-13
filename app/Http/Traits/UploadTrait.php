<?php

namespace App\Http\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait UploadTrait
{
    protected function upload(Request $request, string $input, string $destination)
    {
        if ($request->hasFile($input)) {
            $name = time().'_'.Str::random(16);
            $extension = $request->file($input)->extension();

            return 'storage/'.$request->file($input)->storeAs($destination, "{$name}.{$extension}", 'public');
        }

        return null;
    }
}
