<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreAbstractCallRequest;
use App\Http\Requests\Admin\UpdateAbstractCallRequest;
use App\Models\AbstractCall;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AbstractCallController extends Controller
{
    public function store(StoreAbstractCallRequest $request, Event $event): RedirectResponse
    {
        if ($event->abstractCall()->exists()) {
            throw ValidationException::withMessages(['instructions' => 'This event already has a call for abstracts.']);
        }

        $event->abstractCall()->create([
            ...$request->validated(),
            'public_token' => Str::random(40),
        ]);

        return to_route('events.show', ['event' => $event, 'tab' => 'abstracts'])->with('status', 'Call for abstracts created.');
    }

    public function update(UpdateAbstractCallRequest $request, AbstractCall $abstractCall): RedirectResponse
    {
        $abstractCall->update($request->validated());

        return to_route('events.show', ['event' => $abstractCall->event_id, 'tab' => 'abstracts'])->with('status', 'Call for abstracts updated.');
    }
}
