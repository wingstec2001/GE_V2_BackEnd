<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Country;
use App\Models\Customer;
use App\Http\Requests\Api\CountryRequest;
use Illuminate\Support\Facades\Log;

class CountryController extends Controller
{
    public function All(Request $request)
    {
        $my_query = Country::query();

        if ($request['country_id'] != null) {
            $my_query->where('country_id', $request['country_id']);
        }

        if ($request['country_name'] != null) {
            $my_query->where('country_name', $request['country_name']);
        }
       
        $result = $my_query->get();
        return $this->success($result);
    }

    //Add a country ->Add
    public function Add(CountryRequest $request)
    {
        $country = $request->all();

        $id = Country::create($country)->id;
        return $this->success(['id' => $id]);
    }

    public function CountryIds()
    {
        $countryIds = Country::select('country_id', 'country_name', 'country_code')->orderby('country_id', 'asc')->get();
        return $this->success($countryIds);
    }

    public function Detail(CountryRequest $request, $id)
    {
        $country =  Country::find($id);

        if (!$country) {
            $message = 'country_id :' . $id . ' が見つかりませんでした。';
            Log::warning($message);
            return $this->notFound([$message]);
        }

        return $this->success($country);
    }

    public function Update(CountryRequest $request, $id)
    {
        $target = Country::find($id);
        if (!$target) {
            $message = 'country_id :' . $id . ' が見つかりませんでした。';
            Log::warning($message);
            return $this->notFound([$message]);
        }

        $country = $request->all();

        $ret = $target->Update($country);

        if (!$ret) {
            $message = 'country_id :' . $id . ' 変更に失敗しました。';
            Log::warning($message);
            return $this->notFound([$message]);
        }

        return $this->success('update success');
    }

    public function Delete(Request $request, $id)
    {
        $country = Country::find($id);
        if (!$country) {
            $message = 'Country ID:' . $id . 'が見つかりませんでした。';
            Log::warning($message);
            return $this->setStatusCode(204)->success('no content');
        }

        $country_id = $country->country_id;
        //該当countryが使用されているかどうかをチェックする
        $result = $this->isExist($country_id);
        if (!$result) {
            //未使用の場合、削除する
            $country->delete();
            return $this->setStatusCode(204)->success('no content');
            // return $this->success('delete success');
        } else {
            //使用中の場合、削除不可とする
            return $this->failed(['delete failed']);
        }
    }

    //当該country_idが使用中であるかどうか
    public function isExist($id)
    {
        $tables = ['m_customer'];
        foreach ($tables as $table) {
            $strSQL = 'SELECT country_id FROM ' . $table . " WHERE country_id='" . $id . "'";
            $result = DB::select($strSQL);
            if ($result) {
                return true;
            }
        }
        return false;
    }
}
