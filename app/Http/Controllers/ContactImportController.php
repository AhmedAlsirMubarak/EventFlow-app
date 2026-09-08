<?php

namespace App\Http\Controllers;

use App\Enums\PreferredChannel;
use App\Http\Requests\Admin\StoreContactImportRequest;
use App\Models\Contact;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactImportController extends Controller
{
    public function template(): StreamedResponse
    {
        Gate::authorize('create', Contact::class);

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['full_name', 'job_title', 'department', 'email', 'mobile', 'preferred_channel']);
            fputcsv($handle, [
                'Jane Doe',
                'Director of Operations',
                'Operations',
                'jane.doe@example.com',
                '+968 9000 0000',
                PreferredChannel::Email->value,
            ]);

            fclose($handle);
        }, 'contacts-import-template.csv');
    }

    public function store(StoreContactImportRequest $request, Organization $organization): RedirectResponse
    {
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);

            return back()->with('status', 'The CSV file is empty.');
        }

        $header[0] = preg_replace('/^\x{FEFF}/u', '', (string) $header[0]);
        $columnIndex = array_flip(array_map(fn ($column) => strtolower(trim((string) $column)), $header));

        if (! isset($columnIndex['full_name'])) {
            fclose($handle);

            return back()->with('status', 'The CSV must include a "full_name" column.');
        }

        $cell = function (array $row, string $column) use ($columnIndex): ?string {
            if (! isset($columnIndex[$column])) {
                return null;
            }

            $value = trim((string) ($row[$columnIndex[$column]] ?? ''));

            return $value === '' ? null : $value;
        };

        $imported = 0;
        $errors = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            $data = [
                'full_name' => $cell($row, 'full_name'),
                'job_title' => $cell($row, 'job_title'),
                'department' => $cell($row, 'department'),
                'email' => $cell($row, 'email'),
                'mobile' => $cell($row, 'mobile'),
                'preferred_channel' => $cell($row, 'preferred_channel'),
            ];

            $validator = Validator::make($data, [
                'full_name' => ['required', 'string', 'max:255'],
                'job_title' => ['nullable', 'string', 'max:255'],
                'department' => ['nullable', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255'],
                'mobile' => ['nullable', 'string', 'max:50'],
                'preferred_channel' => ['nullable', Rule::in(array_column(PreferredChannel::cases(), 'value'))],
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$rowNumber}: ".$validator->errors()->first();

                continue;
            }

            $organization->contacts()->create($validator->validated());
            $imported++;
        }

        fclose($handle);

        $status = "Imported {$imported} contact(s).";
        if (count($errors) > 0) {
            $status .= ' '.count($errors).' row(s) skipped — see details below.';
        }

        return back()->with([
            'status' => $status,
            'importErrors' => $errors,
        ]);
    }
}
