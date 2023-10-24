<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Reserve;
use App\Models\ReserveImages;
use App\Http\Requests\Api\ReserveRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReserveController extends Controller
{

  public function All(Request $request)
  {
    $my_query = Reserve::query();

    if ($request['product_id'] != null) {
      //Product_idにより検索
      $my_query->where('product_id', $request['product_id']);
    }

    if ($request['reserve_id'] != null) {
      //Reserve_idにより検索
      $my_query->where('reserve_id', $request['reserve_id']);
    }
    if ($request['today'] != null) {
      $my_query->where([
        ['reserve_open_dt', '<=', $request['today']],
        ['reserve_comp_dt', '>=', $request['today']]
      ]);

    } else {
      if ($request['start_date'] != null) {
        $my_query->where('reserve_open_dt', '>=', $request['start_date']);
      }
      if ($request['end_date'] != null) {
        $my_query->where('reserve_comp_dt', '<=', $request['end_date']);
      }
    }


    $result = $my_query->orderby('id', 'desc')->with('ReserveImages')->get();
    return $this->success($result);
  }

  public function Add(ReserveRequest $request)
  {
    return DB::transaction(function () use ($request) {
      $reserve = $request->all();

      if ($request->has('reserve_images')) {
        $imgs = $request->reserve_images;
        if (count($imgs) != 0) {
          foreach ($imgs as $key => $i) {
            if (!Storage::exists($i)) {
              $message = "img: $i が存在しません。";
              Log::warning($message);
              return  $this->failed([$message], 422);
            } else {
              ReserveImages::create([
                'img_id' => $request['reserve_id'],
                'seq' => $key + 1,
                'reserve_image' => $i,
              ]);
            }
          }
        }
      }

      $id = Reserve::create($reserve)->id;
      //   return $this->success(['id' => $id]);
      return $this->success(['add reserve success']);
    });
  }

  public function Detail(ReserveRequest $request, $id)
  {
    $reserve = Reserve::with('ReserveImages')->find($id);
    if (!$reserve) {
      $message = "id: $id が無効です。";
      Log::warning($message);
      return $this->failed([$message], 422);
    }

    return $this->success($reserve);
  }

  public function Update(Request $request, $id)
  {
    return DB::transaction(function () use ($request, $id) {
      $target = Reserve::find($id);
      if (!$target) {
        $message = 'id:' . $id . ' 変更に失敗しました。';
        Log::warning($message);
        return $this->notFound([$message]);
      }

      //Compare the difference between uploaded images and database images
      $reserve = $request->all();
      $reserveImgs = ReserveImages::where('img_id', $target->reserve_id);
      $old_imgs = $reserveImgs->pluck('reserve_image');
      $new_imgs = $request->reserve_images;
      if (count($old_imgs) != 0 && $old_imgs != $new_imgs) {
        foreach ($old_imgs as $key => $i) {
          if (!in_array($i, $new_imgs) && Storage::exists($i)) {
            Storage::delete($i);
          }
        }
      }

      //delete the data in the reserveImages and add it again
      $reserveImgs->delete();
      if (count($new_imgs) != 0) {
        foreach ($new_imgs as $key => $i) {
          if (!Storage::exists($i)) {
            $message = "img: $i が存在しません。";
            Log::warning($message);
            return  $this->failed([$message], 422);
          } else {
            ReserveImages::create([
              'img_id' => $request['reserve_id'],
              'seq' => $key + 1,
              'reserve_image' => $i,
            ]);
          }
        }
      }

      $ret = $target->Update($reserve);

      if ($ret) {
        return $this->success('update success');
      } else {
        return $this->notFound();
      }
    });
  }

  public function Delete(Request $request, $id)
  {
    return  DB::transaction(function () use ($id) {
      $reserve = Reserve::find($id);
      if (!$reserve) {
        $message = 'id:' . $id . ' が見つかりませんでした。';
        Log::warning($message);
        return $this->setStatusCode(204)->success('no content');
      }
      $reserve_id = $reserve->reserve_id;
      $reserveImages = ReserveImages::where('img_id', $reserve_id);
      $ImagesArray = $reserveImages->pluck('reserve_image');
      if (count($ImagesArray) != 0) {
        foreach ($ImagesArray as $img) {
          if (Storage::exists($img)) {
            Storage::delete($img);
          }
        }
      }
      $reserveImages->delete();
      $reserve->delete();

      // $b = $a->get(['reserve_image']);
      return $this->setStatusCode(204)->success('no content');
    });
  }

  public function Photo(Request $request)
  {
    $reserve_imgs = $request->all();
    $validator = Validator::make($reserve_imgs, [
      'reserve_images' => 'required',
      'reserve_images.*' => 'required|mimes:jpeg,bmp,png,gif',
    ]);
    if ($validator->fails()) {
      return response()->json($validator->errors(), 422);
    }
    $image_files = array();
    foreach ($request->file('reserve_images') as $key => $i) {
      $path = '/' . $i->store('/images/reserve_img');
      $image_files[$key] = $path;
    };

    return $this->success($image_files);
    // return $this->success($reserve_imgs);
  }
}
