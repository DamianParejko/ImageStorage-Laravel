<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImageUploadRequest;
use Illuminate\Http\Request;
use App\Models\Image;
use App\Models\Info;
use App\Models\Sender;
use App\Models\Temperature;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Promise\Create;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class ImageController extends Controller
{
    public $perPage = 10;
    public $apiWeatherUrl = 'https://api.open-meteo.com/v1/forecast?latitude=50.25841&longitude=19.02754&hourly=temperature_2m&timezone=Europe%2FWarsaw&forecast_days=1';

    public function index(Request $request): JsonResponse 
    {
        $page = $request->input('page', 1); 

        $images = Image::with('sender')->paginate($this->perPage, ['*'], 'page', $page);

        return response()->json($images);
    }

    public function upload(ImageUploadRequest $request): JsonResponse
    {   
        try {
            $extension = $request->getImageExtension();
        
            $imageName = date('Y-m-d') . '/' . date('H_i_s') . '_' . Str::uuid() . '.' . $extension;
            $path = $request->file('image')->storeAs('images', $imageName, 'public');

            [$width, $height] = $request->getImageDimensions();
            $size = $request->getImageSize();

            $sender = Sender::firstOrCreate([
                'name' => $request->input('user_name'),  
                'email' => $request->input('email'),  
            ]);

            $image = Image::create([
                'path' => $path,
                'extension' => $extension,
                'size' => $size,
                'width' => $width,
                'height' => $height,
                'sender_id' => $sender->id
            ]);

            $this->saveInfoUploadImage($request->file('image'), $image);
            $this->temperature();

            $image->load('sender');

            return $this->response($image, 201);

        }
        catch(Exception $ex){
            return $this->response(null, 400, $ex->getMessage());
        }
    }

    public function saveInfoUploadImage($file, $image) 
    {
        $exifData = exif_read_data($file);

        $iptcData = [];
        $info = null;

        getimagesize($file->getRealPath(), $info);

        if (isset($info["APP13"])) {
            $iptcData = iptcparse($info["APP13"]);
        }

        $info = new Info();
        $info->exif = json_encode($exifData);
        $info->iptc = json_encode($iptcData);

        $image->info()->save($info);
    }

    public function download($type, $date, $name)
    {
        $filename = $type . '/' . $date . '/' . $name;
        $path = storage_path('app/public/' . $filename);

        if (!File::exists($path)) {
            return $this->response(null, 404);
        }

        return response()->download($path, $name);
    }

    public function delete($id): JsonResponse
    {
        try {
            $image = Image::find($id);

            if (!$image) {
                return $this->response(null, 404);  
            }
    
            $path = storage_path('app/public/' . $image->path);
    
            if (File::exists($path)) {
                File::delete($path);
            }
    
            $image->info()->delete(); 
            $image->delete();
            
            return $this->response(null, 204);
        }
        catch(Exception $ex){
            return $this->response(null, 400, $ex->getMessage());   
        }
    }

    public function temperature() {
        $response = Http::get($this->apiWeatherUrl);

        $dataMeteo = json_decode($response->getBody());
        $temperature = $dataMeteo->hourly->temperature_2m;

        $currentHour = Carbon::now()->format('H');
        $currentHour = intval($currentHour); 

        $currentTemperature = $temperature[$currentHour];

        Temperature::create([
            'temperature' => $currentTemperature
        ]);
    }
}
