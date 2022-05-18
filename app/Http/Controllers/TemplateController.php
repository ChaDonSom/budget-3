<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTemplateRequest;
use App\Http\Requests\UpdateTemplateRequest;
use App\Models\Template;
use Illuminate\Support\Facades\Auth;

class TemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Template::query()
            ->whereBelongsTo(Auth::user())
            ->with(['accounts'])
            ->get()
            ->keyBy('id');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreTemplateRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreTemplateRequest $request)
    {
        $template = Template::create($request->validated());

        // Create pivots to each account
        $template->accounts()->attach(array_map(
            fn($changes) => ([ 'amount' => ($changes['amount'] * 100) * $changes['modifier'] ]),
            $request->accounts
        ));

        return $template;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Template  $template
     * @return \Illuminate\Http\Response
     */
    public function show(Template $template)
    {
        return $template->load(['accounts']);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateTemplateRequest  $request
     * @param  \App\Models\Template  $template
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateTemplateRequest $request, Template $template)
    {
        $template->fill($request->validated())->save();

        // Update pivots to each account
        $template->accounts()->sync(array_map(
            fn($changes) => ([ 'amount' => ($changes['amount'] * 100) * $changes['modifier'] ]),
            $request->accounts
        ));

        $template->load(['accounts']);

        return $template;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Template  $template
     * @return \Illuminate\Http\Response
     */
    public function destroy(Template $template)
    {
        $template->delete();

        return response('Success', 200);
    }
}
