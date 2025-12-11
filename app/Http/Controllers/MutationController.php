<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MutationController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            if ($request->get('action') === 'get_parts') {
                $mat = $request->get('material');
                $parts = Item::where('transaction_type', 'production')->where('material', $mat)
                            ->whereNotNull('part')->where('part', '!=', '')
                            ->select('part')->distinct()->orderBy('part')->pluck('part');
                return response()->json($parts);
            }

            if ($request->get('action') === 'check_stock') {
                $mat = $request->get('material'); $part = $request->get('part'); $type = $request->get('type');
                $filter = [['material', '=', $mat], ['part', '=', $part]];
                $prod = Item::where('transaction_type', 'production')->where($filter)->sum($type);
                $used = Item::where('transaction_type', 'mutation')->where($filter)->sum($type);
                return response()->json(['stock' => round($prod - $used, 2)]);
            }

            if ($request->get('action') === 'pivot_row_details') {
                $id_list = $request->get('id_list');
                $pivotSelections = $request->get('pivot_months', []);
                $metric = $request->get('metric', 'gkg');
                
                if (empty($id_list)) return response()->json(['details' => []]);
                $ids = array_filter(array_map('trim', explode(',', $id_list)));
                
                $details = Item::whereIn('id', $ids)->orderBy('tanggal', 'asc')->get();

                $total_display = 0;
                $calcRow = function($item, $metric) {
                    $val = ($metric === 'mix') ? ($item->gkg + $item->scrap + $item->cakalan) : $item->{$metric};
                    return ($item->transaction_type === 'sale') ? -$val : $val;
                };

                foreach($details as $d) $total_display += $calcRow($d, $metric);

                $first = $details->first();
                $item_key = ($first) ? $first->material . ' - ' . ($first->part ?? 'No Part') : 'N/A';
                
                $selected_months = collect($pivotSelections)->filter(fn($s) => preg_match('/^\d{4}-\d{2}$/', $s))->values()->toArray();
                $monthly_subtotals = $details->groupBy(fn($item) => Carbon::parse($item->tanggal)->format('Y-m'))
                    ->map(function($group) use ($calcRow, $metric) { return $group->sum(fn($i) => $calcRow($i, $metric)); });
                
                if (!empty($selected_months)) $monthly_subtotals = $monthly_subtotals->filter(fn($v, $k) => in_array($k, $selected_months));

                return response()->json([
                    'item_key' => $item_key, 'total_display' => $total_display, 'details' => $details,
                    'monthly_subtotals' => $monthly_subtotals->sortKeysDesc()
                ]);
            }
        }

        $materials = Item::where('transaction_type', 'mutation')->select('material')->distinct()->pluck('material');
        $parts = Item::where('transaction_type', 'mutation')->select('part')->distinct()->pluck('part');
        
        $distinctDates = Item::select(DB::raw('DISTINCT YEAR(tanggal) as year, DATE_FORMAT(tanggal, "%Y-%m") as ym'))
            ->orderBy('year', 'desc')->orderBy('ym', 'desc')->get();
        $distinctYears = $distinctDates->pluck('year')->unique()->sortDesc()->values();
        $distinctYearMonths = $distinctDates->groupBy('year')->map(fn($items) => $items->pluck('ym')->unique()->sort());

        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $material_term = $request->input('material_term');
        $part_term = $request->input('part_term');
        $raw_selections = $request->input('pivot_months', []);
        $mode = $request->input('mode', 'resume');

        $query = Item::query()->orderBy('tanggal', 'desc');

        if ($mode == 'details') {
            $query->where('transaction_type', 'mutation');
            if ($start_date && $end_date) $query->whereBetween('tanggal', [$start_date, $end_date]);
        } else {
            $query->whereIn('transaction_type', ['mutation', 'sale']);
        }

        if ($mode == 'resume') {
            $selected_months = []; $selected_yearly = [];
            $raw_selections = array_filter((array)$raw_selections);
            foreach ($raw_selections as $selection) {
                if (str_starts_with($selection, 'YEARLY-')) $selected_yearly[] = str_replace('YEARLY-', '', $selection); else $selected_months[] = $selection;
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
        $summary_tree = []; 
        $months = [];

        if ($mode == 'resume') {
            $final_months = []; foreach ($selected_yearly as $yearEntry) { $year = explode('|', $yearEntry)[0]; $key = "YEARLY-{$year}|total"; $final_months[$key] = ['key' => $key, 'label' => "Total " . $year, 'type' => 'yearly_total', 'year' => $year]; }
            $temp_months = []; foreach ($selected_months as $ym) { try { $date = Carbon::createFromFormat('Y-m', $ym); $temp_months[$ym] = ['key' => $ym, 'label' => $date->format('M y'), 'type' => 'month', 'year' => $date->format('Y')]; } catch (\Exception $e) { continue; } }
            ksort($temp_months); $months = array_merge($final_months, $temp_months);

            foreach ($items as $item) {
                $year = Carbon::parse($item->tanggal)->format('Y');
                $month_year = Carbon::parse($item->tanggal)->format('Y-m');
                $yearlyKey = "YEARLY-{$year}|total";

                $mat = $item->material;
                $part = $item->part ?? 'NO PART';
                $item_id = $item->id;

                $multiplier = ($item->transaction_type === 'sale') ? -1 : 1;
                $val_gkg = $item->gkg * $multiplier;
                $val_scrap = $item->scrap * $multiplier;
                $val_cakalan = $item->cakalan * $multiplier;
                $total_all = $val_gkg + $val_scrap + $val_cakalan;

                if (!isset($summary_tree[$mat])) $summary_tree[$mat] = ['total_all' => 0, 'months_all' => [], 'parts' => [], 'ids' => []];
                if (!isset($summary_tree[$mat]['parts'][$part])) {
                    $summary_tree[$mat]['parts'][$part] = [
                        'total_all' => 0, 'months_all' => [], 'ids' => [],
                        'total_gkg' => 0, 'total_scrap' => 0, 'total_cakalan' => 0,
                        'months_gkg' => [], 'months_scrap' => [], 'months_cakalan' => []
                    ];
                }

                $summary_tree[$mat]['total_all'] += $total_all;
                $summary_tree[$mat]['months_all'][$month_year] = ($summary_tree[$mat]['months_all'][$month_year] ?? 0) + $total_all;
                $summary_tree[$mat]['months_all'][$yearlyKey] = ($summary_tree[$mat]['months_all'][$yearlyKey] ?? 0) + $total_all;
                $summary_tree[$mat]['ids'][] = $item_id;

                $pNode = &$summary_tree[$mat]['parts'][$part];
                $pNode['ids'][] = $item_id;
                $pNode['total_all'] += $total_all;
                $pNode['months_all'][$month_year] = ($pNode['months_all'][$month_year] ?? 0) + $total_all;
                $pNode['months_all'][$yearlyKey] = ($pNode['months_all'][$yearlyKey] ?? 0) + $total_all;

                $pNode['total_gkg'] += $val_gkg;
                $pNode['months_gkg'][$month_year] = ($pNode['months_gkg'][$month_year] ?? 0) + $val_gkg;
                $pNode['months_gkg'][$yearlyKey] = ($pNode['months_gkg'][$yearlyKey] ?? 0) + $val_gkg;

                $pNode['total_scrap'] += $val_scrap;
                $pNode['months_scrap'][$month_year] = ($pNode['months_scrap'][$month_year] ?? 0) + $val_scrap;
                $pNode['months_scrap'][$yearlyKey] = ($pNode['months_scrap'][$yearlyKey] ?? 0) + $val_scrap;

                $pNode['total_cakalan'] += $val_cakalan;
                $pNode['months_cakalan'][$month_year] = ($pNode['months_cakalan'][$month_year] ?? 0) + $val_cakalan;
                $pNode['months_cakalan'][$yearlyKey] = ($pNode['months_cakalan'][$yearlyKey] ?? 0) + $val_cakalan;
            }
        }

        return view('mutations.index', compact(
            'items', 'mode', 'summary_tree', 'months',
            'start_date', 'end_date', 'material_term', 'part_term',
            'materials', 'parts', 'distinctYears', 'distinctYearMonths', 'raw_selections'
        ));
    }

    public function create() { $materials = Item::where('transaction_type', 'production')->select('material')->distinct()->orderBy('material')->pluck('material'); return view('mutations.create', compact('materials')); }
    public function store(Request $request) { 
        $request->validate(['tanggal'=>'required|date','material'=>'required','part'=>'required','tipe_barang'=>'required|in:gkg,scrap,cakalan','berat'=>'required|numeric|min:0.01']);
        $col = $request->tipe_barang; $filter = [['material','=',$request->material], ['part','=',$request->part]];
        $prod = Item::where('transaction_type','production')->where($filter)->sum($col);
        $used = Item::where('transaction_type','mutation')->where($filter)->sum($col);
        if ($request->berat > ($prod - $used)) return back()->withInput()->with('error', "Stock Produksi Kurang!");
        Item::create(['transaction_type'=>'mutation','tanggal'=>$request->tanggal,'material'=>strtoupper($request->material),'part'=>strtoupper($request->part),'no_lot'=>null,$col=>$request->berat,'berat_mentah'=>0]);
        return redirect()->route('mutations.index')->with('success','Saved');
    }
    public function edit($id) { $item = Item::where('id',$id)->where('transaction_type','mutation')->firstOrFail(); $materials = Item::where('transaction_type','production')->select('material')->distinct()->orderBy('material')->pluck('material'); return view('mutations.edit', compact('item','materials')); }
    public function update(Request $request, $id) { 
        $item = Item::where('id',$id)->where('transaction_type','mutation')->firstOrFail();
        $request->validate(['tanggal'=>'required|date','material'=>'required','part'=>'required','berat'=>'required|numeric|min:0.01']);
        $col = ($item->scrap > 0) ? 'scrap' : (($item->cakalan > 0) ? 'cakalan' : 'gkg');
        $filter = [['material','=',$request->material], ['part','=',$request->part]];
        $prod = Item::where('transaction_type','production')->where($filter)->sum($col);
        $used = Item::where('transaction_type','mutation')->where($filter)->where('id','!=',$id)->sum($col);
        if ($request->berat > ($prod - $used)) return back()->withInput()->with('error', "Stock Kurang!");
        $item->update(['tanggal'=>$request->tanggal,'material'=>strtoupper($request->material),'part'=>strtoupper($request->part),$col=>$request->berat]);
        return redirect()->route('mutations.index')->with('success','Updated');
    }
    public function destroy($id) { Item::where('id',$id)->where('transaction_type','mutation')->delete(); return back()->with('success','Deleted'); }
    
    public function bulkDestroy(Request $request) { $s=(array)$request->input('selected_ids',[]); $ids=[]; foreach($s as $v) $ids=array_merge($ids, explode(',',$v)); Item::whereIn('id',$ids)->where('transaction_type','mutation')->delete(); return back()->with('success','Deleted'); }

    public function downloadCsv(Request $request)
    {
        $ids = (array)$request->input('selected_ids', []);
        if (empty($ids)) return back()->with('error', 'No data selected for download');

        $items = Item::whereIn('id', $ids)->where('transaction_type', 'mutation')->orderBy('tanggal', 'desc')->get();
        // Time removed from filename
        $filename = "mutations_export_" . date('Ymd') . ".csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use($items) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Tanggal', 'Material', 'Part', 'Tipe Barang', 'Berat (KG)']);

            foreach ($items as $item) {
                $tipe = $item->gkg > 0 ? 'GKG' : ($item->scrap > 0 ? 'Scrap' : 'Cakalan');
                $berat = $item->gkg + $item->scrap + $item->cakalan;
                
                fputcsv($file, [
                    $item->tanggal->format('Y-m-d'),
                    $item->material,
                    $item->part,
                    $tipe,
                    $berat
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}