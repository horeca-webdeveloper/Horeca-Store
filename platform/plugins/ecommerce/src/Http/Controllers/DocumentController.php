<?php
namespace Botble\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends BaseController
{
    public function deleteDocument(Request $request)
    {
        // Expecting 'document' in the request, not 'documents'
        $documentPath = $request->input('documents');

        // Ensure the document exists before attempting to delete
        if (Storage::disk('documents')->exists($documentPath)) {
            Storage::disk('documents')->delete($documentPath);

            // Optionally, update your database record if necessary
            // Example: YourModel::update([...]);

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Document not found'], 404);
    }
}
