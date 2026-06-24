<?php

namespace Modules\Core\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Channel\App\Models\Channel;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next)
    {
        $slug    = $request->route('channel_slug');
        $channel = Channel::where('slug', $slug)->first();

        if (! $channel) {
            return response()->json([
                'success' => false,
                'message' => trans('channel::app.channel.not_found'),
            ], 404);
        }

        if (! $channel->isAccessible()) {
            return response()->json([
                'success' => false,
                'message' => trans('channel::app.channel.suspended'),
            ], 403);
        }

        app()->instance('current_channel', $channel);
        app()->instance('current_channel_id', $channel->id);

        return $next($request);
    }
}
