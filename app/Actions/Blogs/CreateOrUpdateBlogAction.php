<?php

namespace App\Actions\Blogs;

use App\Models\Blog;
use App\Models\Faq;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateOrUpdateBlogAction
{
    use AsAction;

    public function handle(array $data, Blog $blog = null)
    {
        if (is_null($blog)) {
            $blog = Blog::create($data);
        } else {
            $blog->update($data);
        }

       resetCache();

        return $blog;
    }
}
