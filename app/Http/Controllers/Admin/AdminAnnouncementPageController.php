<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class AdminAnnouncementPageController extends Controller
{
    /**
     * Display the Admin Announcement Management page.
     */
    public function index(): View
    {
        return view('admin.announcements.index');
    }
}
