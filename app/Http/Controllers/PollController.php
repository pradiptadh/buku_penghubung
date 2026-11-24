<?php

namespace App\Http\Controllers;

use App\Models\Pickup;
use App\Models\SafetyAlert;
use App\Models\User;
use App\Support\Shell;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint ringan untuk polling AJAX — paritas tick() prototipe.
 */
class PollController extends Controller
{
    public function state(Request $request): JsonResponse
    {
        $shell = Shell::build($request);
        $user = $request->user();

        $data = [
            'badges' => $shell['badges'] ?? [],
            'state' => $this->stateHash($user),
            'alarm_active' => false,
            'alarm_muted' => false,
        ];

        if ($user->role === 'ortu') {
            $child = $user->child();
            if ($child) {
                $alert = SafetyAlert::whereDate('date', today())
                    ->where('student_id', $child->id)->whereNull('ack_at')->first();
                $data['alarm_active'] = $alert !== null;
                $data['alarm_muted'] = $alert?->muted ?? false;
            }
        }

        return response()->json($data);
    }

    /**
     * Hash status material: bila berubah, klien me-refresh halaman
     * (kecuali sedang mengetik — paritas guard tick()).
     */
    private function stateHash(User $user): string
    {
        $parts = [];

        if ($user->role === 'ortu') {
            $child = $user->child();
            if ($child) {
                $pickup = Pickup::whereDate('date', today())->where('student_id', $child->id)->first();
                $parts[] = $pickup?->updated_at?->timestamp.'|'.$pickup?->status.'|'.(int) $pickup?->otw.'|'.$pickup?->reminded_at?->timestamp;
                $alert = SafetyAlert::whereDate('date', today())->where('student_id', $child->id)->first();
                $parts[] = $alert?->updated_at?->timestamp.'|'.$alert?->ack_type;
            }
        }

        $parts[] = Pickup::whereDate('date', today())->where('otw', true)->count();
        $parts[] = SafetyAlert::whereDate('date', today())->count();

        return md5(implode(';', $parts));
    }
}
