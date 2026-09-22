<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FileUploadService;

class FilesController extends Controller
{
    public function upload(Request $request, FileUploadService $uploader)
    {
        $request->validate([
            'file' => 'required|file|max:20480', // Max 20MB
        ]);

        $file = $request->file('file');

        // Upload the file and get full URL
        $url = $uploader->upload($file, 'uploads');

        return response()->json([
            'status' => true,
            'message' => 'File uploaded successfully',
            'url' => $url,
        ]);
    }

    /****************************************************************************/
    public function deleteFile(Request $request, FileUploadService $uploader)
{
    $request->validate([
        'url' => 'required|url',
    ]);

    $deleted = $uploader->deleteByUrl($request->input('url'));

    if ($deleted) {
        return response()->json(['status' => true, 'message' => 'File deleted successfully']);
    }

    return response()->json(['status' => false, 'message' => 'File not found or unable to delete'], 404);
}

}
