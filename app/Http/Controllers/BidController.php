<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bid;
use App\Models\ReserveImages;
use App\Http\Requests\Api\BidRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BidController extends Controller
{
    public function All(Request $request)
    {
        $my_query = Bid::query();

        if ($request['product_id'] != null) {
            //Product_idにより検索
            $my_query->where('product_id', $request['product_id']);
        }

        if ($request['bid_id'] != null) {
            //Bid_idにより検索
            $my_query->where('bid_id', $request['bid_id']);
        }
        if ($request['bid_min_price'] != null) {
            $my_query->where('bid_min_price', '>=',$request['bid_min_price']);
        }
        if($request['start_date']!=null){
            $my_query->where('bid_open_dt', '>=',$request['start_date']);
        }
        if($request['end_date']!=null){
            $my_query->where('bid_comp_dt', '<=',$request['end_date']);
        }
        
        $result = $my_query->orderby('id', 'desc')->with('ReserveImages')->get();
        return $this->success($result);
    }

    public function Add(BidRequest $request)
    {
        return DB::transaction(function () use ($request) {
        $bid = $request->all();

        if($request->has('bid_images')) {
            $imgs = $request->bid_images;
            if(count($imgs) != 0) {
                foreach($imgs as $key => $i) {
                    if (!Storage::exists($i)) {
                        $message = "img: $i が存在しません。";
                        Log::warning($message);
                        return  $this->failed([$message], 422);
                    } else {
                        ReserveImages::create([
                            'img_id' => $request['bid_id'],
                            'seq' => $key + 1,
                            'reserve_image' => $i,
                        ]);
                    }
                }
            }
        }
    
        $id = Bid::create($bid)->id;
        //   return $this->success(['id' => $id]);
        return $this->success(['add bid success']);

        });
    }

    public function Detail(BidRequest $request, $id)
    {
        $bid = Bid::with('ReserveImages')->find($id);
        if(!$bid){
        $message = "id: $id が無効です。";
        Log::warning($message);
        return $this->failed([$message], 422);
        }

        return $this->success($bid);
    }

    public function Update(Request $request, $id)
    {
      return DB::transaction(function () use ($request, $id) {
        $target = Bid::find($id);
        if (!$target) {
          $message = 'id:' . $id . ' 変更に失敗しました。';
          Log::warning($message);
          return $this->notFound([$message]);
        }
  
        //Compare the difference between uploaded images and database images
        $bid = $request->all();
        $reserveImgs = ReserveImages::where('img_id', $target->bid_id);
        $old_imgs = $reserveImgs->pluck('reserve_image');
        $new_imgs = $request->bid_images;
        if (count($old_imgs) != 0 && $old_imgs != $new_imgs) {
          foreach($old_imgs as $key => $i) {
            if(!in_array($i, $new_imgs) && Storage::exists($i)) {
              Storage::delete($i);
            }
          }
        }
  
        //delete the data in the reserveImages and add it again
        $reserveImgs->delete();
        if (count($new_imgs) != 0) {
          foreach($new_imgs as $key => $i) {
            if (!Storage::exists($i)) {
                $message = "img: $i が存在しません。";
                Log::warning($message);
                return  $this->failed([$message], 422);
            } else {
                ReserveImages::create([
                    'img_id' => $request['bid_id'],
                    'seq' => $key + 1,
                    'reserve_image' => $i,
                ]);
            }
          }
        }
  
        $ret = $target->Update($bid);
  
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
            $bid = Bid::find($id);
            if (!$bid) {
                $message = 'id:' . $id . ' が見つかりませんでした。';
                Log::warning($message);
                return $this->setStatusCode(204)->success('no content');
            }
            $bid_id = $bid->bid_id;
            $reserveImages = ReserveImages::where('img_id', $bid_id);
            $ImagesArray = $reserveImages->pluck('reserve_image');
            if (count($ImagesArray) != 0) {
                foreach ($ImagesArray as $img) {
                    if(Storage::exists($img)) {
                        Storage::delete($img);
                    }
                }
            }
            $reserveImages->delete();
            $bid->delete();

            // $b = $a->get(['reserve_image']);
            return $this->setStatusCode(204)->success('no content');
        });
    }

    public function Photo(Request $request)
    {
      $bid_imgs = $request->all();
      $validator = Validator::make($bid_imgs, [
        'bid_images' => 'required',
        'bid_images.*' => 'required|mimes:jpeg,bmp,png,gif',
      ]);
      if ($validator->fails()) {
          return response()->json($validator->errors(), 422);
      }
      $image_files = array();
      foreach ($request->file('bid_images') as $key => $i) {
          $path = '/' . $i->store('/images/bid_img');
          $image_files[$key] = $path;
      };
  
      return $this->success($image_files);
    }
}   
