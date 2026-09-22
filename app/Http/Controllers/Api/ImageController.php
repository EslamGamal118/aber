<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Client;
use App\Models\Provider;
use App\Models\Image;
use App\Models\Pdf;

class ImageController extends Controller
{
    public function uploadImage(Request $request, $id = null)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'type' => 'required|string|in:client,vendor',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }
    
        // Validate user ID if provided
        if ($id !== null) {
            if ($request->type == 'client') {
                $user = Client::find($id);
                $userType = 'client';
            } else {
                $user = Provider::find($id);
                $userType = 'vendor';
            }
    
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير موجود'
                ], 404);
            }
        } else {
            $userType = $request->type;
        }
    
        // Check if the image is provided
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = 'image-' . time() . '-' . uniqid() . '.' . $image->getClientOriginalExtension();
            
            // Store the file in the type-specific directory on the public disk
            $path = $image->storeAs("uploads/images/{$userType}", $filename, 'public');
            
            // Generate the full URL to the image
            $fullUrl = asset("storage/{$path}");
            
            // Store Image data in the database
            Image::create([
                'user_id' => $id,
                'image' => $fullUrl,  // Store the full URL
                'type' => $userType
            ]);
    
            return response()->json([
                'success' => true,
                'message' => 'تم رفع الصورة بنجاح',
                'file' => [
                    'filename' => $filename,
                    'path' => $path,
                    'url' => $fullUrl  // Return full URL
                ]
            ]);
        }
    
        return response()->json([
            'success' => false,
            'message' => 'لم يتم تحميل أي صورة'
        ], 400);
    }
    

    /**
     * Get an image
     */
    public function getImagesByTypeAndId(string $type, int $id)
    {
        // Validate the 'type' input to make sure it's either 'client' or 'vendor'
        if (!in_array($type, ['client', 'vendor'])) {
            return response()->json(['message' => 'نوع المستخدم غير صالح'], 400);
        }
    
        // Query the images table for the given type and user ID
        $images = Image::where('type', $type)
                       ->where('user_id', $id)
                       ->get();
    
        // Check if any images were found
        if ($images->isEmpty()) {
            return response()->json(['message' => 'لا توجد صور لهذا المستخدم'], 404);
        }
    
        // Extract the image URLs
        $imageUrls = $images->map(function ($image) {
            return $image->image; // Assuming the 'image' column contains the full URL
        });
    
        return response()->json([
            'success' => true,
            'images' => $imageUrls,
        ]);
    }

     /******************************************************************************************/
    public function uploadPdf(Request $request, $id = null)
    {
        $validator = Validator::make($request->all(), [
            'document' => 'required|mimes:pdf|max:5120',
            'type' => 'required|string|in:client,vendor',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }
    
        // Validate user ID if provided
        if ($id !== null) {
            if ($request->type == 'client') {
                $user = Client::find($id);
                $userType = 'client';
            } else {
                $user = Provider::find($id);
                $userType = 'vendor';
            }
    
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير موجود'
                ], 404);
            }
        } else {
            $userType = $request->type;
        }
    
        if ($request->hasFile('document')) {
            $pdf = $request->file('document');
            $filename = 'document-' . time() . '-' . uniqid() . '.' . $pdf->getClientOriginalExtension();
    
            // Store the file in type-specific directory
            $path = $pdf->storeAs("uploads/documents/{$userType}", $filename, 'public');
    
            // Store the document URL and metadata in the database
            Pdf::create([
                'user_id' => $id,
                'pdf' => asset("storage/{$path}"),
                'type' => $userType
            ]);
    
            return response()->json([
                'success' => true,
                'message' => 'تم رفع المستند بنجاح',
                'file' => [
                    'filename' => $filename,
                    'path' => $path,
                    'url' => asset("storage/{$path}")
                ]
            ]);
        }
    
        return response()->json([
            'success' => false,
            'message' => 'لم يتم تحميل أي مستند'
        ], 400);
    }

    /************************************************************************************* */

    public function getPdfByTypeAndId(string $type, int $id)
    {
        // Validate the 'type' input to make sure it's either 'client' or 'vendor'
        if (!in_array($type, ['client', 'vendor'])) {
            return response()->json(['message' => 'نوع المستخدم غير صالح'], 400);
        }
    
        // Query the Pdf table for the given type and user ID
        $pdfs = Pdf::where('type', $type)
                   ->where('user_id', $id)
                   ->get();
    
        // Check if any PDFs were found
        if ($pdfs->isEmpty()) {
            return response()->json(['message' => 'لا توجد مستندات لهذا المستخدم'], 404);
        }
    
        // Extract the document URLs
        $pdfUrls = $pdfs->map(function ($pdf) {
            return $pdf->pdf; // Assuming the 'document' column contains the full URL
        });
    
        return response()->json([
            'success' => true,
            'documents' => $pdfUrls,
        ]);
    }
    
    
}