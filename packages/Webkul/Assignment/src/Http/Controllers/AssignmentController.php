<?php

namespace Webkul\Assignment\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index()
    {
        return view('assignment::index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store() {}

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return View
     */
    public function edit($id) {}

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update($id) {}

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id) {}
}
