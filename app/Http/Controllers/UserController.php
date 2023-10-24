<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    function __construct()
    {
        $this->middleware('permission:user-read');
        $this->middleware('permission:user-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:user-update', ['only' => ['edit', 'update']]);
        $this->middleware('permission:user-delete', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $my_query = User::query();

        if ($request['name'] != null) {
            $my_query->where('name', $request['name']);
        }
        if ($request['userRoles'] != null) {
            $my_query -> role($request['userRoles']);
        }
        
        $result = $my_query->orderBy('name', 'asc')->get();
       
        return UserResource::collection($result);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $roles = Role::pluck('name')->all();
        return $this->success($roles);
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
            'name' => 'required|between:2,50',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|same:confirm_password|min:6',
            'roles' => 'required'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        $input = $request->all();
        $input['password'] = Hash::make($input['password']);


        $user = User::create($input);
        $user->assignRole($request->input('roles'));


        return $this->success('User created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::find($id);
        return $this->success(new UserResource($user));
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
        if (!$user) {
            $message = 'user_id :' . $id . ' が見つかりませんでした。';
            Log::warning($message);
            return $this->notFound([$message]);
        }

        $user['allRoles'] = Role::pluck('name')->all();
        // $userRole = $user->roles->pluck('name','name')->all();
        return $this->success(new UserResource($user));
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
        $user = User::find($id);
        if (!$user) {
            $message = 'user_id :' . $id . ' 変更に失敗しました。';
            Log::warning($message);
            return $this->notFound([$message]);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,50',
            //.$id 可以和原来邮箱一致
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'same:confirm_password|min:6',
            'roles' => 'required'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $input = $request->all();
        if (!empty($input['password'])) {
            $input['password'] = Hash::make($input['password']);
        } else {
            $input =  Arr::except($input, array('password'));
        }


        $user->update($input);

        $user->syncRoles([]);

        $user->assignRole($request->input('roles'));


        return $this->success('User updated successfully');
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
            'id' => 'exists:users',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
            // return $this->failed($validator->errors(), 422);
        }
        User::find($id)->delete();
        return $this->success('User deleted successfully');
    }
}
