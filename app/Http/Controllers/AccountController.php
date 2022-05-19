<?php

namespace App\Http\Controllers;

use App\Http\Requests\BatchUpdateAccountRequest;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use App\Models\AccountBatchUpdate;
use App\Models\BatchUpdateAccount;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Account::query()
            ->whereBelongsTo(Auth::user())
            // We _would_ like to see batchUpdates for index, but only the not-done ones
            ->with(['batchUpdates' => fn($query) => $query->notDone()])
            ->get()
            ->keyBy('id');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreAccountRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreAccountRequest $request)
    {
        $account = Account::create($request->validated());

        return $account;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Account  $account
     * @return \Illuminate\Http\Response
     */
    public function show(Account $account)
    {
        return $account->load(['batchUpdates' => fn($query) => $query->notDone()]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateAccountRequest  $request
     * @param  \App\Models\Account  $account
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateAccountRequest $request, Account $account)
    {
        $account->fill($request->validated())->save();

        $account->load(['batchUpdates' => fn($query) => $query->notDone()]);

        return $account;
    }

    public function batch(BatchUpdateAccountRequest $request)
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
            $batchUpdate->accounts;
            $httpCode = 202; // 201: created, 202: accepted (but will be processed later)
        }

        $batchUpdate = AccountBatchUpdate::query()
            // Specifically ensure the batchUpdates on each of this one's accounts are only the not-done ones
            ->with(['accounts.batchUpdates' => fn($query) => $query->notDone()])
            ->find($batchUpdate->id);

        return response($batchUpdate, $httpCode);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Account  $account
     * @return \Illuminate\Http\Response
     */
    public function destroy(Account $account)
    {
        $account->delete();

        return response('Success', 200);
    }
}
