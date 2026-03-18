<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class WorkerController extends Controller
{
    public function index(Request $request)
    {
        $workers = User::where('factory_id', $request->user()->factory_id)
            ->where('is_active', true)
            ->select('id','name','email','role','employee_id','department','created_at')
            ->orderBy('name')
            ->get();
        return response()->json(['workers' => $workers]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string',
            'email'       => 'required|email|unique:users',
            'password'    => 'required|min:8',
            'role'        => 'required|in:manager,supervisor,operator,maintenance',
            'employee_id' => 'nullable|string|unique:users',
            'department'  => 'nullable|string',
        ]);

        $worker = User::create([
            'name'        => $request->name,
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
            'role'        => $request->role,
            'employee_id' => $request->employee_id,
            'department'  => $request->department,
            'factory_id'  => $request->user()->factory_id,
        ]);

        return response()->json(['worker' => $worker, 'message' => 'Worker added successfully.'], 201);
    }

    public function destroy(Request $request, User $user)
    {
        $user->update(['is_active' => false]);
        return response()->json(['message' => 'Worker deactivated.']);
    }
}
