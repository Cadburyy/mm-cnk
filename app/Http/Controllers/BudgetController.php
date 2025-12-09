<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $itemNumbers = DB::table('budgets')
            ->select('item_number')
            ->distinct()
            ->whereNotNull('item_number')
            ->where('item_number', '!=', '')
            ->orderBy('item_number')
            ->pluck('item_number');

        $distinctDates = Budget::select(DB::raw('DISTINCT YEAR(effective_date) as year, DATE_FORMAT(effective_date, "%Y-%m") as ym'))
            ->orderBy('year', 'desc')
            ->orderBy('ym', 'desc')
            ->get();

        $distinctYears = $distinctDates->pluck('year')->unique()->sortDesc()->values();
        $distinctYearMonths = $distinctDates->groupBy('year')->map(function ($items) {
            return $items->pluck('ym')->unique()->sort();
        });

        $raw_selections = $request->input('pivot_months', []);
        $item_number_term = $request->input('item_number_term');
        $item_description_term = $request->input('item_description_term'); 
        
        $query = Budget::query();
        $query->orderBy('effective_date', 'desc')->orderBy('item_number', 'asc');

        $selected_months = [];
        $selected_yearly = [];

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
                    $q->orWhere('effective_date', 'LIKE', $ym . '-%');
                }
                foreach ($selected_yearly as $year) {
                    $q->orWhereYear('effective_date', explode('|', $year)[0]); 
                }
            });
        }
        
        if ($item_number_term) {
            $query->where('item_number', 'LIKE', '%' . $item_number_term . '%');
        }
        if ($item_description_term) {
            $query->where('item_description', 'LIKE', '%' . $item_description_term . '%');
        }

        $budgets = $query->get();

        $summary_rows = [];
        $months = [];

        $final_months = [];
        foreach ($selected_yearly as $yearEntry) {
            $parts = explode('|', $yearEntry);
            $year = $parts[0];
            $type = $parts[1] ?? 'total';
            $key = "YEARLY-{$year}|{$type}";
            $label = ($type === 'avg') ? "Avg " . substr($year, 2, 2) : "Total " . $year;
            $final_months[$key] = ['key' => $key, 'label' => $label, 'type' => 'yearly_' . $type, 'year' => $year];
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


        foreach ($budgets as $budget) {
            $year = Carbon::parse($budget->effective_date)->format('Y');
            $month_year = Carbon::parse($budget->effective_date)->format('Y-m');
            $key = $budget->item_number . '||' . $budget->item_description;
            $qty = (float) $budget->budget;
            $item_id = $budget->id;

            if (!isset($summary_rows[$key])) {
                $summary_rows[$key] = [
                    'item_number' => $budget->item_number,
                    'item_description' => $budget->item_description,
                    'total' => 0,
                    'months' => [],
                    'row_ids' => [],
                    'annual_totals' => [],
                    'annual_months_count' => [],
                ];
            }
            
            $summary_rows[$key]['months'][$month_year] = ($summary_rows[$key]['months'][$month_year] ?? 0) + $qty;
            
            $summary_rows[$key]['total'] += $qty;
            
            $summary_rows[$key]['annual_totals'][$year] = ($summary_rows[$key]['annual_totals'][$year] ?? 0) + $qty;
            $summary_rows[$key]['annual_months_count'][$year][$month_year] = true;
            
            $summary_rows[$key]['row_ids'][] = $item_id;
        }

        foreach ($summary_rows as $key => $row) {
            foreach ($selected_yearly as $yearEntry) {
                $parts = explode('|', $yearEntry);
                $year = $parts[0];
                $type = $parts[1] ?? 'total';
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
        
        return view('budget.index', [
            'budgets' => $budgets,
            'pivot_months' => $raw_selections,
            'item_number_term' => $item_number_term,
            'item_description_term' => $item_description_term,
            'itemNumbers' => $itemNumbers,
            'distinctYears' => $distinctYears,
            'distinctYearMonths' => $distinctYearMonths,
            'summary_rows' => array_values($summary_rows),
            'months' => array_values($months),
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'csv_files' => 'required|array',
            'csv_files.*' => 'mimes:csv,txt|max:10240',
        ]);

        $files = $request->file('csv_files');
        $uploadCount = 0;
        $allData = [];
        $errors = [];
        $batchData = [];

        DB::beginTransaction();
        try {
            foreach ($files as $file) {
                $handle = fopen($file->getRealPath(), 'r');
                
                $firstLine = fgets($handle);
                $delimiter = ';';
                if (substr_count($firstLine, ',') > substr_count($firstLine, ';')) {
                    $delimiter = ',';
                }
                rewind($handle);

                $header = fgetcsv($handle, 0, $delimiter);
                if (!$header || count($header) < 4) {
                    fclose($handle);
                    $errors[] = "File {$file->getClientOriginalName()}: Invalid format. Expected 4 columns, got " . (is_array($header) ? count($header) : 0);
                    continue;
                }
                
                $rowNumber = 1;
                while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                    $rowNumber++;
                    if (!is_array($row) || count($row) < 4 || empty(trim($row[0] ?? ''))) {
                        continue;
                    }

                    $row = array_map(function($field) {
                        if ($field === null) return '';
                        $decoded = mb_convert_encoding($field, 'UTF-8', 'UTF-8, ISO-8859-1');
                        return trim($decoded);
                    }, $row);

                    $budget_field = $row[3] ?? '0';
                    $budget_string = str_replace([' '], [''], $budget_field);
                    $budget_string = str_replace(',', '.', $budget_string);
                    $budget_val = (float) $budget_string;

                    try {
                        $effective_date = Carbon::createFromFormat('d/m/Y', trim($row[2]));
                    } catch (\Exception $e) {
                        $errors[] = "File {$file->getClientOriginalName()}, Row {$rowNumber}: Invalid date format '{$row[2]}'. Use d/m/Y";
                        continue;
                    }

                    $batchData[] = [
                        'item_number' => $row[0] ?? null,
                        'item_description' => $row[1] ?? null,
                        'effective_date' => $effective_date->format('Y-m-d'),
                        'budget' => $budget_val,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $uploadCount++;
                }
                fclose($handle);
                
                if (!empty($batchData)) {
                    $allData = array_merge($allData, $batchData);
                    $batchData = [];
                }
            }

            if (!empty($allData)) {
                Budget::insert($allData);
            }
            DB::commit();
            $request->session()->forget('_old_input');
            $message = "{$uploadCount} Budget records uploaded successfully!";
            if (!empty($errors)) {
                $message .= " Warnings: " . implode("; ", array_slice($errors, 0, 3));
            }
            return redirect()->route('budget.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Budget CSV Upload Failed: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->route('budget.index')->with('error', 'Budget data upload failed: ' . $e->getMessage());
        }
    }

    public function bulkDestroy(Request $request)
    {
        if (!auth()->check() || !(method_exists(auth()->user(), 'hasRole') ? auth()->user()->hasRole('Admin|AdminIT') : (auth()->user()->is_admin ?? false))) {
            return back()->with('error', 'Unauthorized access.');
        }

        $selected = (array) $request->input('selected_ids', []);
        
        if (empty($selected)) {
            return back()->with('error', 'No budget items selected for deletion.');
        }

        $all_ids_to_delete = [];
        foreach ($selected as $row_ids_str) {
            $ids = array_filter(array_map('trim', explode(',', $row_ids_str)));
            $all_ids_to_delete = array_merge($all_ids_to_delete, $ids);
        }
        $all_ids_to_delete = array_unique($all_ids_to_delete);
        
        DB::beginTransaction();
        try {
            $deletedCount = Budget::whereIn('id', $all_ids_to_delete)->delete();
            DB::commit();
            
            if ($deletedCount > 0) {
                return back()->with('success', "Successfully deleted {$deletedCount} records across " . count($selected) . " budget item summaries.");
            }
            return back()->with('error', 'Failed to delete any selected records.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Budget Bulk Delete Failed: ' . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', 'Bulk deletion failed: ' . $e->getMessage());
        }
    }
    
    public function edit($id)
    {
        if (!auth()->check() || !(method_exists(auth()->user(), 'hasRole') ? auth()->user()->hasRole('Admin|AdminIT') : (auth()->user()->is_admin ?? false))) {
             return redirect()->route('budget.index')->with('error', 'Unauthorized access.');
        }
        $budget = Budget::findOrFail($id);
        return view('budget.edit', compact('budget'));
    }
    
    public function update(Request $request, $id)
    {
         if (!auth()->check() || !(method_exists(auth()->user(), 'hasRole') ? auth()->user()->hasRole('Admin|AdminIT') : (auth()->user()->is_admin ?? false))) {
             return redirect()->route('budget.index')->with('error', 'Unauthorized access.');
         }
         $request->validate([
             'item_number' => 'required',
             'budget' => 'required|numeric',
         ]);
         
         $budget = Budget::findOrFail($id);
         $budget->update($request->all());
         
         return redirect()->route('budget.index')->with('success', 'Budget record updated successfully.');
    }
}