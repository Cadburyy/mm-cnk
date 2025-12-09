@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-dark">💰 Data Budget Transaksi</h1>
        <div>
            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#uploadCsvModal">
                <i class="fas fa-file-upload me-1"></i> Upload Budget CSV
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('budget.index') }}" class="row mb-4" id="filterForm">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light fw-bold">Filter Data Budget</div>
                <div class="card-body">
                    <div class="row g-3 mb-4">

                        <div class="col-lg-6 col-md-12">
                            <label class="form-label fw-bold">Yearly</label>
                            <div class="card p-3 h-100">
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Pilih Tahun</label>
                                    <div class="dropdown" id="yearlyYearsDropdown">
                                        <button class="btn btn-outline-secondary btn-sm w-100 text-start dropdown-toggle" type="button" id="yearlyYearsBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                            <span id="yearlyYearsLabel">Pilih Tahun</span>
                                        </button>
                                        <div class="dropdown-menu w-100 p-2" style="max-height:200px; overflow-y:auto;" aria-labelledby="yearlyYearsBtn">
                                            @foreach($distinctYears as $year)
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input yearly-year-checkbox" type="checkbox" id="year_yearly_{{ $year }}" value="{{ $year }}">
                                                    <label class="form-check-label small" for="year_yearly_{{ $year }}">{{ $year }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <label class="form-label small mb-1">Mode</label>
                                    <select id="yearlyMode" class="form-control form-control-sm" name="yearly_mode">
                                        <option value="">-- Pilih Mode --</option>
                                        <option value="total">Total</option>
                                        <option value="avg">Rata-rata</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 col-md-12">
                            <label class="form-label fw-bold">Monthly</label>
                            <div class="card p-3 h-100">
                                <div class="row">
                                    <div class="col-5">
                                        <label class="form-label small mb-1">Pilih Tahun</label>
                                        <div class="dropdown" id="monthlyYearsDropdown">
                                            <button class="btn btn-outline-secondary btn-sm w-100 text-start dropdown-toggle" type="button" id="monthlyYearsBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                                <span id="monthlyYearsLabel">Pilih Tahun</span>
                                            </button>
                                            <div class="dropdown-menu w-100 p-2" style="max-height:200px; overflow-y:auto;" aria-labelledby="monthlyYearsBtn">
                                                @foreach($distinctYears as $year)
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input monthly-year-checkbox" type="checkbox" id="year_monthly_{{ $year }}" value="{{ $year }}">
                                                        <label class="form-check-label small" for="year_monthly_{{ $year }}">{{ $year }}</label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-7">
                                        <label class="form-label small mb-1">Pilih Bulan</label>
                                        <div id="monthlyMonthsContainer" class="p-1" style="max-height:160px; overflow:auto; border:1px solid #e9ecef; border-radius:4px;">
                                            @foreach($distinctYearMonths as $yr => $mList)
                                                <div class="monthly-year-group mb-2" data-year="{{ $yr }}" style="display:none;">
                                                    <div class="fw-bold small mb-1">{{ $yr }}</div>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        @foreach($mList as $ym)
                                                            <div class="form-check">
                                                                <input class="form-check-input monthly-month-checkbox" type="checkbox" id="month_{{ $ym }}" value="{{ $ym }}">
                                                                <label class="form-check-label" for="month_{{ $ym }}">{{ \Carbon\Carbon::createFromFormat('Y-m', $ym)->format('M') }}</label>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="small text-muted mt-1"><span id="months-selected-count">0 Bulan terpilih</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mt-3">
                        <div class="col-lg-6 col-md-6">
                            <label class="form-label">Item Number</label>
                            <input list="itemNumbers" name="item_number_term" id="item-number-input"
                                class="form-control form-control-sm" value="{{ $item_number_term }}" autocomplete="off" oninput="this.value = this.value.toUpperCase()">
                            <datalist id="itemNumbers">
                                @foreach($itemNumbers as $num)
                                    <option value="{{ $num }}">
                                @endforeach
                            </datalist>
                        </div>
                        <div class="col-lg-6 col-md-6">
                            <label class="form-label">Item Description</label>
                            <input type="text" name="item_description_term" class="form-control form-control-sm" value="{{ $item_description_term ?? '' }}" autocomplete="off" oninput="this.value = this.value.toUpperCase()">
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-end gap-2">
                    @if(Auth::check() && (method_exists(Auth::user(), 'hasRole') ? auth()->user()->hasRole('Admin|AdminIT') : (auth()->user()->is_admin ?? false)))
                        <button type="button" id="bulkDeleteBtn" class="btn btn-danger">
                            <i class="fas fa-trash-alt me-1"></i> Bulk Delete Selected
                        </button>
                    @endif

                    <div id="pivotHiddenInputsContainer" style="display: none;">
                    </div>

                    <button type="submit" id="applyFiltersBtn" class="btn btn-success shadow">
                        <i class="fas fa-search me-1"></i> Apply Filters
                    </button>
                    <a href="{{ route('budget.index') }}" class="btn btn-outline-secondary shadow">
                        <i class="fas fa-undo me-1"></i> Reset Filter
                    </a>
                </div>
            </div>
        </div>
    </form>

    <form id="bulkDeleteForm" method="POST" action="{{ route('budget.bulkDestroy') }}">
        @csrf
        <div class="card shadow-lg">
            <div class="card-header bg-info text-black fw-bold">
                Hasil Data Budget - Resume (Total {{ count($summary_rows) }} Items)
            </div>
            <div class="card-body p-0">
                @if (empty($summary_rows))
                    <p class="text-center text-muted p-4">Tidak ada data budget yang ditemukan berdasarkan filter yang diterapkan.</p>
                @else
                    <div class="table-responsive" style="max-height: 70vh;">
                        <table class="table table-bordered table-striped table-hover table-sm mb-0">
                            <thead class="bg-light sticky-top">
                                <tr>
                                    <th style="width:36px"><input type="checkbox" id="select-all-resume"></th>
                                    <th class="text-nowrap text-center">Aksi</th>
                                    <th class="text-nowrap">Item Number</th>
                                    <th class="text-nowrap bg-primary text-white">Item Description</th>
                                    @if (count($months) > 0)
                                        @foreach($months as $m)
                                            <th class="text-nowrap text-center" style="min-width:80px;">{{ $m['label'] }}</th>
                                        @endforeach
                                    @endif
                                    <th class="text-nowrap text-center" style="min-width:90px;">Total Budget</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($summary_rows as $row)
                                    @php
                                        $firstId = explode(',', $row['row_ids'])[0] ?? null;
                                    @endphp
                                    <tr>
                                        <td class="select-cell"><input type="checkbox" class="select-resume" name="selected_ids[]" value="{{ $row['row_ids'] ?? '' }}"></td>
                                        <td class="text-nowrap text-center">
                                            @if(Auth::check() && (method_exists(Auth::user(), 'hasRole') ? auth()->user()->hasRole('Admin|AdminIT') : (auth()->user()->is_admin ?? false)) && $firstId)
                                                <a href="{{ route('budget.edit', $firstId) }}" class="btn btn-sm btn-warning me-1" title="Edit First Record"><i class="fas fa-edit"></i></a>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">{{ $row['item_number'] }}</td>
                                        <td style="max-width:300px; background-color: #e7f1ff;">{{ $row['item_description'] }}</td>
                                        @if (count($months) > 0)
                                            @foreach($months as $m)
                                                <td class="text-end font-monospace" style="min-width:80px;">{{ number_format(round($row['months'][$m['key']] ?? 0), 0, ',', '.') }}</td>
                                            @endforeach
                                        @endif
                                        <td class="text-end fw-bold font-monospace bg-light" style="min-width:90px;">{{ number_format(round($row['total']), 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
        <input type="hidden" name="mode" value="resume">
    </form>

    <div class="modal fade" id="uploadCsvModal" tabindex="-1" aria-labelledby="uploadCsvModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="uploadCsvModalLabel">Unggah File CSV Budget Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('budget.store') }}" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        @csrf
                        <p class="text-muted small">
                            Pastikan file Anda memiliki 4 kolom:
                            <span class="fw-bold">Item Number, Item Description, Effective Date (d/m/Y), Budget.</span>
                            Gunakan semicolon (;) atau comma (,) sebagai delimiter.
                        </p>
                        <div class="mb-3">
                            <label for="csv_files" class="form-label">Pilih File CSV (Boleh lebih dari satu)</label>
                            <input type="file" name="csv_files[]" multiple class="form-control" id="csv_files" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload me-1"></i> Upload & Proses Data Budget
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function() {
    const selectedPivot = @json($pivot_months ?? []);
    const $filterForm = $('#filterForm');
    const $bulkDeleteForm = $('#bulkDeleteForm');
    const $pivotHiddenInputsContainer = $('#pivotHiddenInputsContainer');

    function updateYearsLabel(selector, labelId) {
        const checked = $(selector + ':checked');
        let label = 'Pilih Tahun';
        if (checked.length === 1) {
            label = checked.val();
        } else if (checked.length > 1) {
            label = checked.length + ' Tahun terpilih';
        }
        $(labelId).text(label);
    }

    function updateMonthsCount() {
        const count = ($('.monthly-month-checkbox:checked').length) || 0;
        $('#months-selected-count').text(count + ' Bulan terpilih');
    }

    function rebuildPivotHiddenInputs() {
        $pivotHiddenInputsContainer.empty();

        const yearlyYears = $('.yearly-year-checkbox:checked').map(function(){ return $(this).val(); }).get() || [];
        const yearlyMode = $('#yearlyMode').val(); 
        
        if (yearlyMode && yearlyYears.length > 0) {
            yearlyYears.forEach(function(y) {
                const val = 'YEARLY-' + y + '|' + yearlyMode;
                $('<input>').attr({type: 'hidden', name: 'pivot_months[]', value: val}).appendTo($pivotHiddenInputsContainer);
            });
        }

        const monthly = $('.monthly-month-checkbox:checked').map(function(){ return $(this).val(); }).get() || [];
        monthly.forEach(function(ym) {
            $('<input>').attr({type: 'hidden', name: 'pivot_months[]', value: ym}).appendTo($pivotHiddenInputsContainer);
        });
    }

    function syncMonthlyGroupsVisibility() {
        const selYears = $('.monthly-year-checkbox:checked').map(function(){ return $(this).val(); }).get() || [];
        $('.monthly-year-group').each(function(){
            const y = $(this).data('year') + '';
            if (selYears.indexOf(y) !== -1) $(this).show();
            else $(this).hide(); 
        });
    }

    $('.yearly-year-checkbox').on('change', function() {
        updateYearsLabel('.yearly-year-checkbox', '#yearlyYearsLabel');
        rebuildPivotHiddenInputs();
    });
    
    $('#yearlyMode').on('change', function() {
        rebuildPivotHiddenInputs();
    });

    $('.monthly-year-checkbox').on('change', function(){
        updateYearsLabel('.monthly-year-checkbox', '#monthlyYearsLabel');
        syncMonthlyGroupsVisibility();
        rebuildPivotHiddenInputs(); 
        updateMonthsCount();
    });

    $(document).on('change', '.monthly-month-checkbox', function(){
        rebuildPivotHiddenInputs();
        updateMonthsCount();
    });

    $('.dropdown-menu').on('click', function (e) {
        e.stopPropagation();
    });

    $filterForm.on('submit', function(e) {
        rebuildPivotHiddenInputs(); 
    });

    (function syncFromServerPivot() {
        const yearlyYears = [];
        const monthlyYears = [];
        const monthVals = [];
        let yearlyMode = '';

        selectedPivot.forEach(function(p) {
            const pStr = String(p);
            if (pStr.startsWith('YEARLY-')) {
                const parts = pStr.replace('YEARLY-','').split('|');
                const y = parts[0];
                yearlyYears.push(y);
                if (parts.length > 1) {
                    yearlyMode = parts[1];
                }
            } else if (/^\d{4}-\d{2}$/.test(pStr)) {
                monthVals.push(pStr);
            }
        });

        yearlyYears.forEach(function(y) {
            $(`#year_yearly_${y}`).prop('checked', true);
        });
        if ($('#yearlyMode option[value="' + yearlyMode + '"]').length) {
            $('#yearlyMode').val(yearlyMode);
        }
        updateYearsLabel('.yearly-year-checkbox', '#yearlyYearsLabel');

        monthVals.forEach(function(m){
            const year = m.slice(0,4);
            
            if (monthlyYears.indexOf(year) === -1) {
                monthlyYears.push(year);
            }
            
            $(`#month_${m}`).prop('checked', true);
        });
        
        monthlyYears.forEach(function(y){
            $(`#year_monthly_${y}`).prop('checked', true);
        });
        updateYearsLabel('.monthly-year-checkbox', '#monthlyYearsLabel');

        syncMonthlyGroupsVisibility();
        rebuildPivotHiddenInputs();
        updateMonthsCount();
    })();

    $('#select-all-resume').on('change', function() { 
        $('.select-resume').prop('checked', $(this).is(':checked')); 
    });
    
    $('#bulkDeleteBtn').on('click', function() {
        const selected = $('.select-resume:checked').map(function(){ return $(this).val(); }).get();
        if (selected.length === 0) { 
            alert('Pilih setidaknya satu baris untuk dihapus secara massal.'); 
            return; 
        }
        if (confirm(`Anda yakin ingin menghapus ${selected.length} item budget (dan semua transaksi yang terkait)? Tindakan ini tidak dapat dibatalkan.`)) {
             $('#bulkDeleteForm')[0].submit();
        }
    });
});
</script>

@endsection