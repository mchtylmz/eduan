<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Test;
use Illuminate\Http\Request;

class TestsController extends Controller
{
    public function index()
    {
        return view('backend.tests.index', [
            'title' => __('Sınavlar')
        ]);
    }

    public function create()
    {
        return view('backend.tests.create', [
            'title' => __('Sınav Oluştur')
        ]);
    }

    public function edit(Test $test)
    {
        return view('backend.tests.edit', [
            'title' => __('Sınavı Düzenle'),
            'test' => $test
        ]);
    }

    public function sections(Test $test)
    {
        return view('backend.tests.sections', [
            'title' => __('Sınavı Bölümleri'),
            'test' => $test
        ]);
    }
}
