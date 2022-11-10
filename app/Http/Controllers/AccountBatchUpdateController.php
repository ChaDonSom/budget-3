<?php

namespace App\Http\Controllers;

use App\Events\AccountBatchUpdateSaved;
use App\Http\Requests\BatchUpdateAccountRequest;
use App\Http\Requests\UpdateAccountBatchUpdateRequest;
use App\Models\AccountBatchUpdate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountBatchUpdateController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(AccountBatchUpdate::class, 'batch_update');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->account_ids) $accountIds = json_decode(request()->account_ids);
        else $accountIds = null;
        if (request()->date_range) {
            $dateRange = json_decode(request()->date_range);
            $dateRange = count($dateRange) ? $dateRange : null;
        } else $dateRange = null;

        $user = Auth::user();
        $users = collect([$user])->concat($user->usersWhoSharedToMe)->concat($user->sharedUsers)->unique();
        return AccountBatchUpdate::query()
            ->when(
                request()->account_id,
                fn($query) => $query->whereHas(
                        'accounts',
                        fn($query) => $query->where('accounts.id', request()->account_id)
                    )
                    ->with(['accounts']),
                fn($query) => $query
                    ->when(
                        $accountIds,
                        fn($query) => $query->whereHas(
                                'accounts',
                                fn($query) => $query->whereIn('accounts.id', $accountIds)
                            )
                            ->with(['accounts']),
                        fn($query) => $query->with('accounts')   
                    )
            )
            ->when(
                !request()->boolean('include_future') && !$dateRange,
                fn($query) => $query->whereNotNull('done_at')
            )
            ->when(
                $dateRange && !request()->boolean('include_future'),
                fn($query) => $query->where('date', '>=', $dateRange[0])->where('date', '<=', $dateRange[1] ?? $dateRange[0])
            )
            ->whereIn('user_id', $users->pluck('id')->values())
            ->orderBy('date', 'desc')
            ->orderBy('updated_at', 'desc')
            ->paginate(50)
            ->withQueryString();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(BatchUpdateAccountRequest $request)
    {
        $batch = DB::select('select batch from audits where batch is not null order by created_at desc limit 1');
        // If nothing has been done yet, a blank slate...
        if (!count($batch)) $batch = 1;
        else $batch = $batch[0]->batch + 1;

        $now = Carbon::now()->tz(Auth::user()->timezone)->format('Y-m-d');
        $sameDate = $now == $request->date;
        $pastDate = !$sameDate && Carbon::createFromFormat('Y-m-d', $request->date, Auth::user()->timezone)->isPast();

        $batchUpdate = AccountBatchUpdate::create([
            'user_id' => Auth::user()->id,
            'batch' => $batch,
            'date' => $request->date,
            'notify_me' => $request->notify_me,
            'weeks' => $request->weeks,
            'note' => $request->note,
        ]);

        // Attach to accounts with change info
        $batchUpdate->accounts()->attach(array_map(
            fn($changes) => ([ 'amount' => ($changes['amount'] * 100) * $changes['modifier'] ]),
            $request->accounts
        ));

        if ($sameDate || $pastDate) {
            // Perform changes now and return them
            /**
             * @var Collection<Account>
             */
            $result = $batchUpdate->handle();
            $httpCode = 200;
        } else {
            // Schedule changes for later and let the client know
            AccountBatchUpdateSaved::dispatch($batchUpdate);
            $httpCode = 202; // 201: created, 202: accepted (but will be processed later)
        }

        $batchUpdate = AccountBatchUpdate::query()
            // Specifically ensure the batchUpdates on each of this one's accounts are only the not-done ones
            ->with([
                'accounts.batchUpdates' => fn($query) => $query->notDone(),
                'accounts.favoritedUsers',
            ])
            ->find($batchUpdate->id);

        return response($batchUpdate, $httpCode);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(AccountBatchUpdate $batchUpdate)
    {
        return $batchUpdate->load('accounts');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateAccountBatchUpdateRequest $request, AccountBatchUpdate $batchUpdate) {
        $now = Carbon::now()->tz(Auth::user()->timezone)->format('Y-m-d');
        $sameDate = $now == $request->date;
        $pastDate = !$sameDate && Carbon::createFromFormat('Y-m-d', $request->date, Auth::user()->timezone)->isPast();

        // Reverse now to use the old values before we change them. If no changes, we'll reapply it anyway.
        if ($batchUpdate->done_at) $batchUpdate->reverse();

        $batchUpdate->fill([
            'user_id' => Auth::user()->id,
            'date' => $request->date,
            'notify_me' => $request->notify_me,
            'note' => $request->note,
            'weeks' => $request->weeks,
        ])->save();

        // Attach to accounts with change info
        $batchUpdate->accounts()->sync(array_map(
            fn($changes) => ([ 'amount' => ($changes['amount'] * 100) * $changes['modifier'] ]),
            $request->accounts
        ));

        if ($sameDate || $pastDate) {
            // Perform changes now and return them
            /**
             * @var Collection<Account>
             */
            $result = $batchUpdate->handle();
            $httpCode = 200;
        } else {
            // Schedule changes for later (done already by this line) and let the client know
            AccountBatchUpdateSaved::dispatch($batchUpdate);
            $httpCode = 202; // 201: created, 202: accepted (but will be processed later)
        }

        $batchUpdate = AccountBatchUpdate::query()
            // Specifically ensure the batchUpdates on each of this one's accounts are only the not-done ones
            ->with([
                'accounts.batchUpdates' => fn($query) => $query->notDone(),
                'accounts.favoritedUsers',
            ])
            ->find($batchUpdate->id);

        return response($batchUpdate, $httpCode);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(AccountBatchUpdate $batchUpdate)
    {
        if ($batchUpdate->done_at) $batchUpdate->reverse();

        $batchUpdate->delete();

        return response('Success', 200);
    }
}
