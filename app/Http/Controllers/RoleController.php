<?php

namespace App\Http\Controllers;

use GrahamCampbell\ResultType\Success;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\RoleResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    function __construct()
    {
        $this->middleware('permission:role-read');
        $this->middleware('permission:role-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:role-update', ['only' => ['edit', 'update']]);
        $this->middleware('permission:role-delete', ['only' => ['destroy']]);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // $roles = Role::orderBy('id', 'DESC')->paginate(5);
        // $roles = Role::orderBy('name', 'ASC')->select('id','name')->get();
        $my_query = Role::query();

        if ($request['name'] != null) {
            $my_query->where('name', $request['name']);
        }
       
        $result = $my_query->orderBy('name', 'ASC')->select('id','name')->get();

        return $this->success($result);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $permissions = Permission::Pluck('name');
        return $this->success(compact('permissions'));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:roles,name',
            'permissions' => 'required',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        $role = Role::create(['name' => $request->input('name')]);
        $role->syncPermissions($request->input('permissions'));

        return $this->success('Role created successfully');
    }
    /**
     * Get all permissions of the role.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $role = Role::find($id);
        return $this->success(new RoleResource($role));
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $role = Role::find($id);
        if($role){
            $permissions = Permission::Pluck('name');
            return $this->success(new RoleResource($role, $permissions));
        }else{
            $message = 'role_id :' . $id . ' is not found';
            Log::warning($message);
            return $this->notFound([' 選択されたidは無効です。']);
        }
       
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
        $role = Role::find($id);
        if(!$role){
            $message = 'role_id :' . $id . ' 変更に失敗しました。';
            Log::warning($message);
            return $this->notFound([' 変更に失敗しました。']);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'permissions' => 'required',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }


       
        $role->name = $request->input('name');
        $role->save();


        $role->syncPermissions($request->input('permissions'));

        return $this->success('Role updated successfully');
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $validator = Validator::make(['id' => $id], [
            'id' => 'exists:roles',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
            // return $this->failed($validator->errors(), 422);
        }
        DB::table("roles")->where('id', $id)->delete();
        return $this->success('Role deleted successfully');
    }
}
