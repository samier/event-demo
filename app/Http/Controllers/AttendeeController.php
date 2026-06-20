<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendeeRequest;
use App\Models\Event;
use App\Services\AttendeeRegistrationService;
use Illuminate\Http\JsonResponse;

class AttendeeController extends Controller
{
    public function __construct(private readonly AttendeeRegistrationService $registrations) {}

    /**
     * Register interest / attendance for an event and email a confirmation.
     */
    public function store(StoreAttendeeRequest $request, Event $event): JsonResponse
    {
        $result = $this->registrations->register($event, $request->validated());

        return response()->json($result, 201);
    }
}
