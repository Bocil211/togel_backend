<?php

namespace App\Http\Controllers\BackEnd;

use App\Http\Controllers\Controller;
use App\Models\BackEnd\Authorizable;
use App\Models\BackEnd\Permission;
use App\Models\BackEnd\Role;
use App\Models\BackEnd\User;
use Config;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
Use Alert;
use DB;
class UserController extends Controller
{
    use Authorizable;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $result = User::latest()->paginate();

        return view('backend.user.index', compact('result'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $roles = Role::pluck('name', 'id');

        return view('backend.user.new', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'bail|required|min:2',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'roles' => 'required|min:1',
        ]);

        // hash password
        $request->merge(['password' => bcrypt($request->get('password'))]);

        // Create the user
        if ($user = User::create($request->except('roles', 'permissions'))) {

            $this->syncPermissions($request, $user);


            Alert::success('User has been added successfully !!!','Success')->persistent("OK");

        } else {
            Alert::error('Unable to create user !!!','Error')->persistent("OK");


        }

        return redirect()->route(Config::get('sysconfig.prefix') . 'users.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = User::find($id);
        $roles = Role::pluck('name', 'id');
        $permissions = Permission::all('name', 'id');

        return view('backend.user.edit', compact('user', 'roles', 'permissions'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'bail|required|min:2',
            'email' => 'required|email|unique:users,email,' . $id,
            'roles' => 'required|min:1',
        ]);

        // Get the user
        $user = User::findOrFail($id);

        // Update user
        $user->fill($request->except('roles', 'permissions', 'password'));

        // check for password change
      //  if ($request->get('password')) {
          //  $user->password = bcrypt($request->get('password'));
       // }

        // Handle the user roles
        $this->syncPermissions($request, $user);

        $user->save();
        if ($request->get('password')) {
           // $user->password = bcrypt($request->get('password'));
           Auth::logoutOtherDevices($request->get('password'));
        }
        Alert::success('User has been updated successfully !!!','Success')->persistent("OK");

        return redirect()->route(Config::get('sysconfig.prefix') . 'users.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     * @internal param Request $request
     */
    public function destroy($id)
    {
        if (Auth::user()->id == $id) {
            return response()->json(['title' => 'Deletion of currently', 'message' => 'Deletion of currently logged in user is not allowed ', 'status' => 'info']);
        }

        if (User::findOrFail($id)->delete()) {
            //flash()->success('User has been deleted');
            return response()->json(['title' => 'Success', 'message' => 'User has been deleted ', 'status' => 'success']);

        } else {
            return response()->json(['title' => 'Not Completed', 'message' => 'User has no deleted ', 'status' => 'warning']);

        }

        //return redirect()->back();
    }

    /**
     * Sync roles and permissions
     *
     * @param Request $request
     * @param $user
     * @return string
     */
    private function syncPermissions(Request $request, $user)
    {
        // Get the submitted roles
        $roles = $request->get('roles', []);
        $permissions = $request->get('permissions', []);

        // Get the roles
        $roles = Role::find($roles);

        // check for current role changes
        if (!$user->hasAllRoles($roles)) {
            // reset all direct permissions for user
            $user->permissions()->sync([]);
        } else {
            // handle permissions
            $user->syncPermissions($permissions);
        }

        $user->syncRoles($roles);

        return $user;
    }

    public function buildDataTable(Request $request)
    {

       // DB::statement(DB::raw('set @rownum=0'));
        $users = User::select(['id', 'name', 'email', 'created_at'])->with('roles');
        $datatables = Datatables::of($users)->addColumn('roles', function (User $user) {
            return $user->roles ? $user->roles->implode('name', ', ') : '';
        })->addColumn('action', function () {return '';})->editColumn('action', function ($user) {return '<a href="' . url(Config::get('sysconfig.prefix') . '/users') . '/' . $user->id . '/edit" class="btn btn-minier btn-primary"><i class="glyphicon glyphicon-edit"></i></a>
        <button class="btn-delete btn btn-minier btn-danger delete_user"  data-id="' . $user->id . '">
            <i class="glyphicon glyphicon-trash"></i>
        </button>
       ';})->editColumn('name','<a href="' . url(Config::get('sysconfig.prefix') . '/users') . '/{{ $id }}/edit" >{{ $name }}</a>')->rawColumns(['name','action'])->addIndexColumn();

       return $datatables->make(true);
    }
}
