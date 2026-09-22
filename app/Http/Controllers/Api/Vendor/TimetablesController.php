<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Timetable;
class TimetablesController extends Controller
{
    public function index(){
        try{
            // get all timetables
            $provider_id = Auth::user()->id;
            $timetables = Timetable::where('provider_id', $provider_id)->get();
            return response()->json(['status' => 'success', 'timetables' => $timetables], 200);
        }catch(\Exception $e){
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    /**************************************************************************************/
    // Store New Timetable
    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'day' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        try{
            $provider_id = Auth::user()->id;

            $timetable = new Timetable();
            $timetable->provider_id = $provider_id;
            $timetable->day = $request->day;
            $timetable->start_time = $request->start_time;
            $timetable->end_time = $request->end_time;
            $timetable->save();
            
            return response()->json(['status' => 'success', 'message' => 'تم حفظ موعد العمل بنجاح' , 'data' => $timetable], 200);
        }catch(\Exception $e){
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    /***************************************************************************************/
    // show Timetable
    public function show($id){
        try{
            $timetable = Timetable::where('provider_id', Auth::user()->id)->findOrFail($id);            
            return response()->json(['status' => 'success', 'timetable' => $timetable], 200);
        }catch(\Exception $e){
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    /****************************************************************************************/
    // ُUpdate Timetable
    public function update(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'day' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 422);
        }

        try{
            $provider_id = Auth::user()->id;

            
            $timetable = Timetable::findOrFail($id);

            if($timetable->provider_id != $provider_id){
                return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
            }
        $timetable->day = $request->day;
        $timetable->start_time = $request->start_time;
        $timetable->end_time = $request->end_time;
        $timetable->save();
        
            return response()->json(['status' => 'success', 'message' => 'تم تعديل موعد العمل بنجاح' , 'data' => $timetable], 200);
        }catch(\Exception $e){
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    /***************************************************************************************/
    // Delete Timetable
    public function destroy($id){
        try{

            $provider_id = Auth::user()->id;
            
            $timetable = Timetable::findOrFail($id);

            if($timetable->provider_id != $provider_id){
                return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
            }
            $timetable->delete();
            return response()->json(['status' => 'success', 'message' => 'تم حذف موعد العمل بنجاح'], 200);
        }catch(\Exception $e){
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
