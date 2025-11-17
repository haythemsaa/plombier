<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProviderAvailability;
use App\Models\ProviderBlockedSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Availability",
 *     description="Provider availability management"
 * )
 */
class AvailabilityController extends Controller
{
    /**
     * Get provider's weekly availability.
     */
    public function getWeeklyAvailability(Request $request, string $providerId): JsonResponse
    {
        $availability = ProviderAvailability::where('provider_id', $providerId)
            ->orderByRaw("FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')")
            ->get()
            ->groupBy('day_of_week');

        return response()->json([
            'success' => true,
            'availability' => $availability,
        ]);
    }

    /**
     * Set weekly availability (provider only).
     */
    public function setWeeklyAvailability(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->type !== 'provider') {
            return response()->json(['error' => 'Only providers can set availability'], 403);
        }

        $validator = Validator::make($request->all(), [
            'availability' => 'required|array',
            'availability.*.day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'availability.*.start_time' => 'required|date_format:H:i',
            'availability.*.end_time' => 'required|date_format:H:i|after:availability.*.start_time',
            'availability.*.is_available' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Delete existing availability
        ProviderAvailability::where('provider_id', $user->id)->delete();

        // Create new availability
        foreach ($request->availability as $slot) {
            ProviderAvailability::create([
                'provider_id' => $user->id,
                'day_of_week' => $slot['day_of_week'],
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
                'is_available' => $slot['is_available'] ?? true,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Availability updated successfully',
        ]);
    }

    /**
     * Get available slots for a provider on a specific date.
     */
    public function getAvailableSlots(Request $request, string $providerId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|after_or_equal:today',
            'duration' => 'integer|min:30|max:480', // 30 min to 8 hours
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $date = Carbon::parse($request->date);
        $duration = $request->get('duration', 60);

        $slots = ProviderAvailability::getAvailableSlots($providerId, $date, $duration);

        return response()->json([
            'success' => true,
            'date' => $date->toDateString(),
            'slots' => $slots,
        ]);
    }

    /**
     * Block a time slot (provider only).
     */
    public function blockSlot(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->type !== 'provider') {
            return response()->json(['error' => 'Only providers can block slots'], 403);
        }

        $validator = Validator::make($request->all(), [
            'start_datetime' => 'required|date|after:now',
            'end_datetime' => 'required|date|after:start_datetime',
            'reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $blocked = ProviderBlockedSlot::create([
            'provider_id' => $user->id,
            'start_datetime' => $request->start_datetime,
            'end_datetime' => $request->end_datetime,
            'reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'blocked_slot' => $blocked,
        ], 201);
    }

    /**
     * Get blocked slots.
     */
    public function getBlockedSlots(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->type !== 'provider') {
            return response()->json(['error' => 'Only providers can view blocked slots'], 403);
        }

        $blocked = ProviderBlockedSlot::where('provider_id', $user->id)
            ->where('end_datetime', '>=', now())
            ->orderBy('start_datetime')
            ->get();

        return response()->json([
            'success' => true,
            'blocked_slots' => $blocked,
        ]);
    }

    /**
     * Delete a blocked slot.
     */
    public function deleteBlockedSlot(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if ($user->type !== 'provider') {
            return response()->json(['error' => 'Only providers can delete blocked slots'], 403);
        }

        $blocked = ProviderBlockedSlot::where('provider_id', $user->id)
            ->findOrFail($id);

        $blocked->delete();

        return response()->json([
            'success' => true,
            'message' => 'Blocked slot deleted successfully',
        ]);
    }

    /**
     * Toggle instant booking (provider only).
     */
    public function toggleInstantBooking(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->type !== 'provider') {
            return response()->json(['error' => 'Only providers can toggle instant booking'], 403);
        }

        $user->update([
            'instant_booking_enabled' => !$user->instant_booking_enabled,
        ]);

        return response()->json([
            'success' => true,
            'instant_booking_enabled' => $user->instant_booking_enabled,
        ]);
    }
}
