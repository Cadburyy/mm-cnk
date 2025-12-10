<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Weight;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax() && $request->get('action') === 'pivot_row_details') {
            $id_list = $request->get('id_list');
            
            if (empty($id_list)) {
                return response()->json(['details' => []]);
            }

            $ids = array_filter(array_map('trim', explode(',', $id_list)));

            if (empty($ids)) {
                return response()->json(['details' => []]);
            }

            $details = Item::whereIn('id', $ids)
                ->orderBy('tanggal', 'asc')
                ->get();

            $total_gkg = $details->sum('gkg');
            $total_scrap = $details->sum('scrap');
            
            $first_item = $details->first();
            $item_key = ($first_item) ? $first_item->material : 'N/A';

            return response()->json([
                'item_key' => $item_key,
                'total_qty' => $total_gkg, 
                'details' => $details,
                'total_scrap' => $total_scrap
            ]);
        }

        $materials = Item::select('material')->distinct()->whereNotNull('material')->orderBy('material')->pluck('material');
        $parts = Item::select('part')->distinct()->whereNotNull('part')->orderBy('part')->pluck('part');
        
        $distinctDates = Item::select(DB::raw('DISTINCT YEAR(tanggal) as year, DATE_FORMAT(tanggal, "%Y-%m") as ym'))
            ->orderBy('year', 'desc')
            ->orderBy('ym', 'desc')
            ->get();

        $distinctYears = $distinctDates->pluck('year')->unique()->sortDesc()->values();
        $distinctYearMonths = $distinctDates->groupBy('year')->map(function ($items) {
            return $items->pluck('ym')->unique()->sort();
        });

        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $material_term = $request->input('material_term');
        $part_term = $request->input('part_term');
        $raw_selections = $request->input('pivot_months', []);
        $mode = $request->input('mode', 'resume');

        if ($mode === 'details' && (!auth()->check() || !(method_exists(auth()->user(), 'hasRole') ? auth()->user()->hasRole('Admin|AdminIT') : (auth()->user()->is_admin ?? false)))) {
            $mode = 'resume';
        }

        $query = Item::query();
        $query->orderBy('tanggal', 'desc');

        if ($mode == 'details' && $start_date && $end_date) {
            $query->whereBetween('tanggal', [$start_date, $end_date]);
        }

        $selected_months = [];
        $selected_yearly = [];

        if ($mode == 'resume') {
            $raw_selections = array_filter((array)$raw_selections);
            foreach ($raw_selections as $selection) {
                if (str_starts_with($selection, 'YEARLY-')) {
                    $selected_yearly[] = str_replace('YEARLY-', '', $selection);
                } else {
                    $selected_months[] = $selection;
                }
            }

            if (!empty($selected_months) || !empty($selected_yearly)) {
                $query->where(function($q) use ($selected_months, $selected_yearly) {
                    foreach ($selected_months as $ym) {
                        $q->orWhere('tanggal', 'LIKE', $ym . '-%');
                    }
                    foreach ($selected_yearly as $yearEntry) {
                        $year = explode('|', $yearEntry)[0];
                        $q->orWhereYear('tanggal', $year);
                    }
                });
            }
        }

        if ($material_term) {
            $query->where('material', 'LIKE', '%' . $material_term . '%');
        }
        if ($part_term) {
            $query->where('part', 'LIKE', '%' . $part_term . '%');
        }

        $items = $query->get();

        $summary_rows = [];
        $months = [];

        if ($mode == 'resume') {
            $final_months = [];
            $yearly_mode = $request->input('yearly_mode', 'total'); 

            foreach ($selected_yearly as $yearEntry) {
                $parts = explode('|', $yearEntry);
                $year = $parts[0];
                $type = $parts[1] ?? $yearly_mode; 
                if ($type === 'avg') {
                    $key = "YEARLY-{$year}|avg";
                    $final_months[$key] = ['key' => $key, 'label' => "Avg " . substr($year, 2, 2), 'type' => 'yearly_avg', 'year' => $year];
                } else {
                    $key = "YEARLY-{$year}|total";
                    $final_months[$key] = ['key' => $key, 'label' => "Total " . $year, 'type' => 'yearly_total', 'year' => $year];
                }
            }

            $temp_months = [];
            foreach ($selected_months as $ym) {
                try {
                    $date = Carbon::createFromFormat('Y-m', $ym);
                    $temp_months[$ym] = ['key' => $ym, 'label' => $date->format('M y'), 'type' => 'month', 'year' => $date->format('Y')];
                } catch (\Exception $e) {
                    continue;
                }
            }
            ksort($temp_months);
            $months = array_merge($final_months, $temp_months);

            foreach ($items as $item) {
                $year = Carbon::parse($item->tanggal)->format('Y');
                $month_year = Carbon::parse($item->tanggal)->format('Y-m');

                $key = $item->material;
                $qty = $item->gkg;
                $item_id = $item->id;
                
                if (!isset($summary_rows[$key])) {
                    $summary_rows[$key] = [
                        'material' => $item->material,
                        'total_gkg' => 0,
                        'months' => [],
                        'row_ids' => [],
                        'annual_totals' => [],
                        'annual_months_count' => [],
                    ];
                }

                $summary_rows[$key]['months'][$month_year] = ($summary_rows[$key]['months'][$month_year] ?? 0) + $qty;
                $summary_rows[$key]['total_gkg'] += $qty;
                $summary_rows[$key]['annual_totals'][$year] = ($summary_rows[$key]['annual_totals'][$year] ?? 0) + $qty;
                $summary_rows[$key]['annual_months_count'][$year][$month_year] = true;
                $summary_rows[$key]['row_ids'][] = $item_id;
            }

            foreach ($summary_rows as $key => $row) {
                foreach ($selected_yearly as $yearEntry) {
                    $parts = explode('|', $yearEntry);
                    $year = $parts[0];
                    $type = $parts[1] ?? $yearly_mode;
                    $annual_total = $row['annual_totals'][$year] ?? 0;
                    
                    if ($type === 'avg') {
                        $unique_months_in_data = count($row['annual_months_count'][$year] ?? []);
                        $yearly_key = "YEARLY-{$year}|avg";
                        $summary_rows[$key]['months'][$yearly_key] = ($unique_months_in_data > 0) ? ($annual_total / $unique_months_in_data) : 0;
                    } else {
                        $yearly_key = "YEARLY-{$year}|total";
                        $summary_rows[$key]['months'][$yearly_key] = $annual_total;
                    }
                }
                $summary_rows[$key]['row_ids'] = implode(',', array_unique($summary_rows[$key]['row_ids']));

                unset($summary_rows[$key]['annual_totals']);
                unset($summary_rows[$key]['annual_months_count']);
            }
        }

        return view('items.index', compact(
            'items', 'mode', 'summary_rows', 'months',
            'start_date', 'end_date', 'material_term', 'part_term',
            'materials', 'parts', 'distinctYears', 'distinctYearMonths', 'raw_selections'
        ));
    }

    public function create()
    {
        $weights = Weight::all(); 
        return view('items.create', compact('weights'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'material' => 'required|string',
            'berat_mentah' => 'nullable|numeric|min:0',
            'gpcs' => 'nullable|numeric|min:0',
            'gkg' => 'nullable|numeric|min:0',
            'scrap' => 'nullable|numeric|min:0',
            'cakalan' => 'nullable|numeric|min:0',
        ]);

        $data = $request->all();
        $this->uppercaseFields($data);

        Item::create($data);

        return redirect()->route('items.index')->with('success', 'Data successfully created.');
    }

    public function edit($id)
    {
        $item = Item::findOrFail($id);
        $weights = Weight::all(); 
        return view('items.edit', compact('item', 'weights'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'material' => 'required|string',
            'berat_mentah' => 'nullable|numeric|min:0',
            'gpcs' => 'nullable|numeric|min:0',
            'gkg' => 'nullable|numeric|min:0',
            'scrap' => 'nullable|numeric|min:0',
            'cakalan' => 'nullable|numeric|min:0',
        ]);

        $item = Item::findOrFail($id);
        
        $data = $request->all();
        $this->uppercaseFields($data);

        $item->update($data);

        return redirect()->route('items.index')->with('success', 'Data successfully updated.');
    }

    public function destroy($id)
    {
        Item::destroy($id);
        return back()->with('success', 'Data successfully deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $selected = (array) $request->input('selected_ids', []);
        
        if (empty($selected)) {
            return back()->with('error', 'No items selected.');
        }
        
        $all_ids = [];
        foreach($selected as $val) {
            $all_ids = array_merge($all_ids, explode(',', $val));
        }

        Item::whereIn('id', $all_ids)->delete();
        
        return back()->with('success', 'Selected records deleted.');
    }

    private function uppercaseFields(array &$data) {
        $fields = ['material', 'part', 'no_lot', 'kode'];
        foreach ($fields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = strtoupper($data[$field]);
            }
        }
    }
}