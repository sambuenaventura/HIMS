<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\ArchivedPatients;
use Intervention\Image\ImageManager;
use Intervention\Image\Facades\Image;
use App\Models\Patients;
use App\Models\ServiceRequest;

class RadTechController extends Controller
{
    public function index(Request $request)
    {
        // Get the authenticated radtech's ID
        $radtechId = auth()->user()->id;
        
        // Query for pending service requests assigned to the authenticated radtech
        $requests = ServiceRequest::query()
                                   ->where('status', 'pending')
                                   ->whereIn('procedure_type', ['X_ray', 'Ultrasound', 'CT_Scan'])
                                   ->paginate(10); 
        return view('radtech.index', compact('requests'));
    }
    
    

    public function acceptRequest(Request $request, $request_id)
    {
        // Validate the request data
        $request->validate([
            'password' => 'required|string', // Add any additional validation rules for the password
        ]);
    
        // Check if the password matches the user's password
        if (!Hash::check($request->password, Auth::user()->password)) {
            return redirect()->back()->with('message', 'Incorrect password. Please try again.'); // Redirect back with an error message
        }
    
        $request = ServiceRequest::where('request_id', $request_id)->firstOrFail();
        $request->status = 'accepted';
        $request->receiver_id = auth()->id(); // Set the receiver_id to the authenticated user's ID
        $request->save();
    
        return redirect()->back()->with('message', 'Request accepted successfully');
    }
    
    public function declineRequest(Request $request, $request_id)
    {
        // Validate the request data
        $request->validate([
            'password' => 'required|string', // Add any additional validation rules for the password
        ]);
    
        // Check if the password matches the user's password
        if (!Hash::check($request->password, Auth::user()->password)) {
            return redirect()->back()->with('message', 'Incorrect password. Please try again.'); // Redirect back with an error message
        }
    
        $request = ServiceRequest::where('request_id', $request_id)->firstOrFail();
        $request->status = 'declined';
        $request->receiver_id = auth()->id(); // Set the receiver_id to the authenticated user's ID
        $request->save();
    
        return redirect()->back()->with('message', 'Request declined successfully');
    }

  
    public function viewRequests(Request $request)
    {
        // Retrieve the search query and request status from the request
        $search = $request->input('search');
        $status = $request->input('status');
    
        // Get the authenticated user's ID
        $userId = auth()->id();
    
        // Query based on request status
        $query = ServiceRequest::query()->where('receiver_id', $userId);
        switch ($status) {
            case 'accepted':
                $query->where('status', 'accepted');
                break;
            case 'completed':
                $query->where('status', 'completed');
                break;
            case 'declined':
                $query->where('status', 'declined');
                break;
            default:
                // Default to pending requests
                $query->where('status', 'pending');
                break;
        }
    
        // Apply search query if it exists
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('patient_id', 'like', '%' . $search . '%')
                    ->orWhere('procedure_type', 'like', '%' . $search . '%');
            });
        }
    
        // Retrieve requests and paginate the results
        $requests = $query->paginate(10); // Adjust pagination limit as needed
    
        // Pass request status to the view to maintain consistency
        return view('radtech.requests', compact('requests', 'status'));
    }


    public function processResult(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'message' => ['required', 'string'],
            'image' => ['required', 'image', 'max:2048'], // Max size 2MB, adjust as needed
            'request_id' => ['required', 'exists:requests,request_id'],
        ]);

        // Find the request by request ID
        $requestEntry = ServiceRequest::findOrFail($validatedData['request_id']);

        // Store the uploaded image
        // $imagePath = $request->file('image')->store('request_images', 'public');
        $imagePath = $request->file('image')->storeAs('request_images', $request->file('image')->getClientOriginalName(), 'public');
        // Update the request information
        $requestEntry->message = $validatedData['message'];
        $requestEntry->status = 'completed';
        $requestEntry->image = $imagePath; // Save the image path
        $requestEntry->save();

        // Redirect back to the previous page after processing the result
        return redirect()->route('radtech.requests', ['status' => 'accepted'])
                        ->with('message', 'Result sent successfully');
    }

    public function show($id, $request_id)
    {
        $patient = Patients::findOrFail($id);
        $request = ServiceRequest::findOrFail($request_id);
        return view('radtech.view', ['patient' => $patient, 'request' => $request]);
    }
    
}


