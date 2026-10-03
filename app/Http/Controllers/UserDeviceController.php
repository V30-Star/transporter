<?php

namespace App\Http\Controllers;

use App\Models\UserDevice;
use Illuminate\Support\Facades\Auth;

class UserDeviceController extends Controller
{
    public function index()
    {
        $devices = UserDevice::leftJoin('sysuser', 'user_device.fsysuserid', '=', 'sysuser.fsysuserid')
            ->select('user_device.*', 'sysuser.fname')
            ->orderByRaw("fstatus = 'pending' desc")
            ->orderByDesc('fcreated')
            ->get();

        return view('userdevice.index', compact('devices'));
    }

    public function approve(UserDevice $userdevice)
    {
        $userdevice->update([
            'fstatus' => 'approved',
            'fapprovedby' => (Auth::guard('sysuser')->user() ?? Auth::user())->fsysuserid,
            'fapproved' => now(),
        ]);

        return back()->with('success', "Komputer {$userdevice->fsysuserid} disetujui.");
    }

    public function reject(UserDevice $userdevice)
    {
        $userdevice->delete();

        return back()->with('success', "Komputer {$userdevice->fsysuserid} ditolak/dicabut.");
    }
}
