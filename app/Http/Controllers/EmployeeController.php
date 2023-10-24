<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Http\Requests\Api\EmployeeRequest;
use Illuminate\Support\Facades\Log;

class EmployeeController extends Controller
{
    public function All(Request $request)
    {
        // $employees =  Employee::all();
        $my_query = Employee::query();

        if ($request['employee_id'] != null) {
            $my_query->where('employee_id', $request['employee_id']);
        }

        if ($request['employee_name'] != null) {
            $my_query->where('employee_name', $request['employee_name']);
        }
       
        $result = $my_query->get();
        return $this->success($result);
    }

    public function EmployeeIds()
    {
        $employeeIds = Employee::select('employee_id', 'employee_sei', 'employee_mei')->orderby('employee_id', 'asc')->get();
        return $this->success($employeeIds);
    }

    public function Add(EmployeeRequest $request)
    {
        $employee = $request->all();

        $id = Employee::create($employee)->id;
        return $this->success(['id' => $id]);
    }

    public function Detail(EmployeeRequest $request, $id)
    {
        $employee =  Employee::find($id);
        if (!$employee) {
            $message = 'employee_id : ' . $id . ' が見つかりませんでした。';
            Log::warning($message);
            return $this->notFound([$message]);
        }

        return $this->success($employee);
    }

    public function Update(EmployeeRequest $request, $id)
    {
        $target = Employee::find($id);
        if (!$target) {
            $message = 'employee_id : ' . $id . ' 変更に失敗しました。';
            Log::warning($message);
            return $this->notFound([' 変更に失敗しました。']);
        }

        $employee = $request->all();

        $ret = $target->Update($employee);

        if ($ret) {
            return $this->success('update success');
        } else {
            return $this->notFound();
        }
    }

    public function Delete(EmployeeRequest $request, $id)
    {
        $employee = Employee::find($id);
        if (!$employee) {
            $message = 'Country ID:' . $id . 'is not found';
            Log::warning($message);
            return $this->setStatusCode(204)->success('no content');
        }
        $employee->delete();
        return $this->setStatusCode(204)->success('no content');
    }
}
