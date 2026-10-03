// Note: I haven't written PHP professionally. My hands-on experience is in JavaScript/TypeScript.
// Both are dynamically typed, C-style languages, so I assumed that the behavior is broadly similar
// and that the syntax is readable for me. I used AI to double-check the syntax details.

namespace App\Domain\KeyResult;
use App\Models\KeyResult;
use App\Models\Workspace;
class KeyResultProgressUpdater
{
    public function updateProgress(Workspace $workspace, int $keyResultId, float $newValue): KeyResult
    {
        // First of all, I think newValue should be a finite number, so I added this check to make sure we don't have a NaN problem here.
        // It's a small bug which would just lead to a user error but wouldn't affect data consistency.
        if (!is_finite($newValue)) {
            throw new \InvalidArgumentException('Progress must be a finite number');
        }

        // I also decided that the Workspace model was imported for a reason, which is very important both from a business-logic perspective and for security (SOC 2) reasons.
        // So I'm assuming that every KeyResult has a link to a Workspace, which means we have to look only inside the user's workspace to make sure there is no security breach and that all users' data is properly separated.
        // For me this is a very important issue with a possibly huge impact on the business, because it allows wrong data to end up in the production DB.
        $keyResult = KeyResult::where('workspace_id', $workspace->id)
            ->findOrFail($keyResultId); // Additional check: I changed find to findOrFail, since the value could be null and then the whole logic would break with a 500 error on $keyResult->target. This is not the most important bug either, and it is very easy to fix and debug.

        if ($newValue < 0 || $newValue > $keyResult->target) {
            throw new \InvalidArgumentException('Invalid progress value');
        } // I think there was a typo here, since the closing brace was missing.

        $keyResult->previous_value = $keyResult->current_value;
        // I also see a lack of historical data. Judging by the provided code, no history is saved, so we don't know the previous value after saving.
        // As a minimum, I would introduce a new property like previous_value to store it. In an ideal world, though, I would use an event-based approach for such operations (an append-only log of changes), so that every change is stored: who made it, when, and what changed.
        
        $keyResult->current_value = $newValue;
        $keyResult->save();

        // I also see that the cache was updated without invalidation. In that case we could have a critical issue where the user assumes the data was not saved, or doesn't see the result immediately.
        // Note: the original code called Cache without importing it. I'm not a PHP expert, so I'm not sure how Cache resolves inside this namespace. I used the global alias \Cache, and this is worth double-checking. For that syntax i used Claude to help me to write the proper invalidation function. 
        \Cache::forget("ws:{$workspace->id}:kr_progress:{$keyResultId}");

        return $keyResult;
    }
}


// If I could fix only one thing before this ships, it would be the workspace check.
// The other problems are visible and recoverable: a 500 error, a stale cache entry, a missing history. 
// The workspace issue is silent: any user who guesses an ID could modify another customer's data, and nobody would notice until a customer complains.
// In a multi-tenant B2B SaaS that is a data isolation failure and a trust problem (and a SOC 2 issue), so for me it comes first.