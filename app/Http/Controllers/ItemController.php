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
        // --- BAGIAN AJAX (Popup Modal Details) ---
        if ($request->ajax() && $request->get('action') === 'pivot_row_details') {
            $id_list = $request->get('id_list');
            $pivotSelections = $request->get('pivot_months', []);
            $metric = $request->get('metric', 'gkg'); // Default metric
            
            if (empty($id_list)) return response()->json(['details' => []]);

            $ids = array_filter(array_map('trim', explode(',', $id_list)));
            if (empty($ids)) return response()->json(['details' => []]);

            $details = Item::whereIn('id', $ids)->orderBy('tanggal', 'asc')->get();

            // Hitung total display untuk header modal berdasarkan metric yang diklik
            // Jika metric 'mix' (Level 1 & 2), kita tampilkan total GKG+Scrap+Cakalan
            $total_display = 0;
            if ($metric === 'mix') {
                $total_display = $details->sum(function($i) {
                    return $i->gkg + $i->scrap + $i->cakalan;
                });
            } else {
                switch ($metric) {
                    case 'scrap': $total_display = $details->sum('scrap'); break;
                    case 'cakalan': $total_display = $details->sum('cakalan'); break;
                    default: $total_display = $details->sum('gkg'); break;
                }
            }

            // Info Header Modal
            $first = $details->first();
            $item_key = ($first) ? $first->material . ' - ' . ($first->part ?? 'No Part') : 'N/A';
            
            $metricLabel = match($metric) {
                'mix' => 'Total Berat (GKG + Scrap + Cakalan)',
                'scrap' => 'Total Scrap',
                'cakalan' => 'Total Cakalan',
                default => 'Total GKG (Produk)'
            };
            $item_key .= " [" . $metricLabel . "]";

            // Logika Monthly Subtotals
            $selected_months = collect($pivotSelections)
                ->filter(fn($s) => preg_match('/^\d{4}-\d{2}$/', $s))
                ->values()
                ->toArray();

            $monthly_subtotals = $details->groupBy(function($item) {
                return Carbon::parse($item->tanggal)->format('Y-m');
            })->map(function($group) use ($metric) {
                if ($metric === 'mix') {
                    return $group->sum(fn($i) => $i->gkg + $i->scrap + $i->cakalan);
                }
                if ($metric === 'scrap') return $group->sum('scrap');
                if ($metric === 'cakalan') return $group->sum('cakalan');
                return $group->sum('gkg'); 
            });
            
            if (!empty($selected_months)) {
                $monthly_subtotals = $monthly_subtotals->filter(function ($value, $key) use ($selected_months) {
                    return in_array($key, $selected_months);
                });
            }

            $monthly_subtotals = $monthly_subtotals->sortKeysDesc();

            return response()->json([
                'item_key' => $item_key,
                'total_display' => $total_display,
                'metric' => $metric,
                'details' => $details,
                'monthly_subtotals' => $monthly_subtotals
            ]);
        }
        // --- END AJAX ---

        // Dropdown Lists
        $materials = Item::select('material')->distinct()->whereNotNull('material')->orderBy('material')->pluck('material');
        $parts = Item::select('part')->distinct()->whereNotNull('part')->orderBy('part')->pluck('part');
        
        // Date Logic
        $distinctDates = Item::select(DB::raw('DISTINCT YEAR(tanggal) as year, DATE_FORMAT(tanggal, "%Y-%m") as ym'))
            ->orderBy('year', 'desc')->orderBy('ym', 'desc')->get();
        $distinctYears = $distinctDates->pluck('year')->unique()->sortDesc()->values();
        $distinctYearMonths = $distinctDates->groupBy('year')->map(fn($items) => $items->pluck('ym')->unique()->sort());

        // Inputs
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $material_term = $request->input('material_term');
        $part_term = $request->input('part_term');
        $raw_selections = $request->input('pivot_months', []);
        $mode = $request->input('mode', 'resume');

        // Security Check for Details Mode
        if ($mode === 'details' && (!auth()->check() || !(method_exists(auth()->user(), 'hasRole') ? auth()->user()->hasRole('Admin|AdminIT') : (auth()->user()->is_admin ?? false)))) {
            $mode = 'resume';
        }

        // Base Query
        $query = Item::query()->orderBy('tanggal', 'desc');

        // Filter Apply
        if ($mode == 'details' && $start_date && $end_date) $query->whereBetween('tanggal', [$start_date, $end_date]);

        $selected_months = [];
        $selected_yearly = [];

        if ($mode == 'resume') {
            $raw_selections = array_filter((array)$raw_selections);
            foreach ($raw_selections as $selection) {
                if (str_starts_with($selection, 'YEARLY-')) $selected_yearly[] = str_replace('YEARLY-', '', $selection);
                else $selected_months[] = $selection;
            }
            if (!empty($selected_months) || !empty($selected_yearly)) {
                $query->where(function($q) use ($selected_months, $selected_yearly) {
                    foreach ($selected_months as $ym) $q->orWhere('tanggal', 'LIKE', $ym . '-%');
                    foreach ($selected_yearly as $yearEntry) $q->orWhereYear('tanggal', explode('|', $yearEntry)[0]);
                });
            }
        }

        if ($material_term) $query->where('material', 'LIKE', '%' . $material_term . '%');
        if ($part_term) $query->where('part', 'LIKE', '%' . $part_term . '%');

        $items = $query->get();

        // Data processing for View
        $summary_tree = []; 
        $months = [];

        if ($mode == 'resume') {
            $final_months = [];
            $yearly_mode = $request->input('yearly_mode', 'total'); 

            foreach ($selected_yearly as $yearEntry) {
                $parts_y = explode('|', $yearEntry);
                $year = $parts_y[0];
                $type = $parts_y[1] ?? $yearly_mode; 
                // Kita gunakan logika Total saja untuk konsistensi hierarki
                $key = "YEARLY-{$year}|total";
                $final_months[$key] = ['key' => $key, 'label' => "Total " . $year, 'type' => 'yearly_total', 'year' => $year];
            }

            $temp_months = [];
            foreach ($selected_months as $ym) {
                try {
                    $date = Carbon::createFromFormat('Y-m', $ym);
                    $temp_months[$ym] = ['key' => $ym, 'label' => $date->format('M y'), 'type' => 'month', 'year' => $date->format('Y')];
                } catch (\Exception $e) { continue; }
            }
            ksort($temp_months);
            $months = array_merge($final_months, $temp_months);

            foreach ($items as $item) {
                $year = Carbon::parse($item->tanggal)->format('Y');
                $month_year = Carbon::parse($item->tanggal)->format('Y-m');
                $yearlyKey = "YEARLY-{$year}|total";

                $mat = $item->material;
                $part = $item->part ?? 'NO PART';
                
                $gkg = $item->gkg;
                $scrap = $item->scrap;
                $cakalan = $item->cakalan;
                
                // --- MODIFIKASI: Total Berat untuk Parent Levels ---
                $total_all = $gkg + $scrap + $cakalan;
                
                $item_id = $item->id;

                // Struktur Tree: Material -> Part
                if (!isset($summary_tree[$mat])) {
                    $summary_tree[$mat] = [
                        'total_all' => 0, // Total GKG+Scrap+Cakalan
                        'months_all' => [],
                        'ids' => [],
                        'parts' => []
                    ];
                }
                
                if (!isset($summary_tree[$mat]['parts'][$part])) {
                    $summary_tree[$mat]['parts'][$part] = [
                        'total_all' => 0, // Total GKG+Scrap+Cakalan
                        'total_gkg' => 0,
                        'total_scrap' => 0,
                        'total_cakalan' => 0,
                        'months_all' => [],
                        'months_gkg' => [],
                        'months_scrap' => [],
                        'months_cakalan' => [],
                        'ids' => []
                    ];
                }

                // Agregasi Material Level (Level 1) - Menggunakan Total Semua
                $summary_tree[$mat]['total_all'] += $total_all;
                $summary_tree[$mat]['months_all'][$month_year] = ($summary_tree[$mat]['months_all'][$month_year] ?? 0) + $total_all;
                $summary_tree[$mat]['months_all'][$yearlyKey] = ($summary_tree[$mat]['months_all'][$yearlyKey] ?? 0) + $total_all;
                $summary_tree[$mat]['ids'][] = $item_id;

                // Agregasi Part Level (Level 2)
                $pNode = &$summary_tree[$mat]['parts'][$part];
                $pNode['ids'][] = $item_id;
                
                // Total Mix untuk Part Header
                $pNode['total_all'] += $total_all;
                $pNode['months_all'][$month_year] = ($pNode['months_all'][$month_year] ?? 0) + $total_all;
                $pNode['months_all'][$yearlyKey] = ($pNode['months_all'][$yearlyKey] ?? 0) + $total_all;

                // Level 3 Breakdown: GKG
                $pNode['total_gkg'] += $gkg;
                $pNode['months_gkg'][$month_year] = ($pNode['months_gkg'][$month_year] ?? 0) + $gkg;
                $pNode['months_gkg'][$yearlyKey] = ($pNode['months_gkg'][$yearlyKey] ?? 0) + $gkg;

                // Level 3 Breakdown: Scrap
                $pNode['total_scrap'] += $scrap;
                $pNode['months_scrap'][$month_year] = ($pNode['months_scrap'][$month_year] ?? 0) + $scrap;
                $pNode['months_scrap'][$yearlyKey] = ($pNode['months_scrap'][$yearlyKey] ?? 0) + $scrap;

                // Level 3 Breakdown: Cakalan
                $pNode['total_cakalan'] += $cakalan;
                $pNode['months_cakalan'][$month_year] = ($pNode['months_cakalan'][$month_year] ?? 0) + $cakalan;
                $pNode['months_cakalan'][$yearlyKey] = ($pNode['months_cakalan'][$yearlyKey] ?? 0) + $cakalan;
            }
        }

        return view('items.index', compact(
            'items', 'mode', 'summary_tree', 'months',
            'start_date', 'end_date', 'material_term', 'part_term',
            'materials', 'parts', 'distinctYears', 'distinctYearMonths', 'raw_selections'
        ));
    }

    public function create() { $weights = Weight::all(); return view('items.create', compact('weights')); }
    public function store(Request $request) { 
        $request->validate(['tanggal'=>'required|date','material'=>'required|string']); 
        $d=$request->all(); $this->uppercaseFields($d); Item::create($d); 
        return redirect()->route('items.index')->with('success','Created'); 
    }
    public function edit($id) { $item = Item::findOrFail($id); $weights = Weight::all(); return view('items.edit', compact('item','weights')); }
    public function update(Request $request, $id) { 
        $request->validate(['tanggal'=>'required|date','material'=>'required|string']);
        $item = Item::findOrFail($id); $d=$request->all(); $this->uppercaseFields($d); $item->update($d);
        return redirect()->route('items.index')->with('success','Updated'); 
    }
    public function destroy($id) { Item::destroy($id); return back()->with('success','Deleted'); }
    public function bulkDestroy(Request $request) { 
        $s=(array)$request->input('selected_ids',[]); if(empty($s)) return back()->with('error','No selection');
        $ids=[]; foreach($s as $v) $ids=array_merge($ids, explode(',',$v));
        Item::whereIn('id',$ids)->delete(); return back()->with('success','Deleted');
    }
    private function uppercaseFields(array &$data) { 
        foreach(['material','part','no_lot','kode'] as $f) if(isset($data[$f])&&is_string($data[$f])) $data[$f]=strtoupper($data[$f]); 
    }
}