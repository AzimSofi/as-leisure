<?php

namespace App\Http\Controllers;

use App\Models\Roadtax;
use App\Http\Requests\StoreRoadtaxRequest;
use App\Http\Requests\UpdateRoadtaxRequest;
use Illuminate\Http\Request; // Add this import

class RoadtaxController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Roadtax::query();

        if ($request->has('vehicle_number')) {
            $query->where('vehicle_number', $request->input('vehicle_number'));
        }

        return response()->json(['data' => $query->get()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoadtaxRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Roadtax $roadtax)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Roadtax $roadtax)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoadtaxRequest $request, Roadtax $roadtax)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Roadtax $roadtax)
    {
        //
    }
}
