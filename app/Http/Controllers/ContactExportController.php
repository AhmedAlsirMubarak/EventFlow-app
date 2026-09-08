<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Organization;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactExportController extends Controller
{
    public function index(Organization $organization): StreamedResponse
    {
        Gate::authorize('viewAny', Contact::class);

        $contacts = $organization->contacts()->orderBy('full_name')->get();

        return response()->streamDownload(function () use ($contacts) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['full_name', 'job_title', 'department', 'email', 'mobile', 'preferred_channel', 'portal_access']);

            foreach ($contacts as $contact) {
                fputcsv($handle, [
                    $contact->full_name,
                    $contact->job_title,
                    $contact->department,
                    $contact->email,
                    $contact->mobile,
                    $contact->preferred_channel?->value,
                    $contact->hasPortalAccess() ? 'Active' : '',
                ]);
            }

            fclose($handle);
        }, 'contacts-'.Str::slug($organization->name).'.csv');
    }
}
