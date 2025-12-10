@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-dark">📊 Data Transaksi Barang</h1>
        <div>
            <a href="{{ route('items.create') }}" class="btn btn-primary shadow-sm">
                <i class="fas fa-plus me-1"></i> Input Data
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('items.index') }}" class="row mb-4" id="filterForm">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light fw-bold">Filter Data Barang</div>
                <div class="card-body">
                    @if ($mode == 'details')
                        <div class="row g-3 mb-2">
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label fw-bold">Start Date</label>
                                <input type="date" name="start_date" value="{{ $start_date }}" class="form-control">
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label fw-bold">End Date</label>
                                <input type="date" name="end_date" value="{{ $end_date }}" class="form-control">
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label">Material</label>
                                <input list="materials" name="material_term" id="material-input"
                                    class="form-control form-control-sm" value="{{ $material_term }}" autocomplete="off" oninput="this.value = this.value.toUpperCase()">
                                <datalist id="materials">
                                    @foreach($materials as $mat)
                                        <option value="{{ $mat }}">
                                    @endforeach
                                </datalist>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label">Part</label>
                                <input list="parts" name="part_term" id="part-input"
                                    class="form-control form-control-sm" value="{{ $part_term ?? '' }}" autocomplete="off" oninput="this.value = this.value.toUpperCase()">
                                <datalist id="parts">
                                    @foreach($parts as $part)
                                        <option value="{{ $part }}">
                                    @endforeach
                                </datalist>
                            </div>
                        </div>
                    @else
                        <div class="row g-3">
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
                                                <input type="hidden" name="yearly_years[]" id="yearlyYearsHidden">
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
                                            <option value="">--Pilih Mode--</option>
                                            <option value="total" {{ (request('yearly_mode') == 'total') ? 'selected' : '' }}>Total</option>
                                            <option value="avg" {{ (request('yearly_mode') == 'avg') ? 'selected' : '' }}>Rata-rata</option>
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
                        <div class="row g-3 mt-4">
                            <div class="col-6">
                                <label class="form-label">Material</label>
                                <input list="materials" name="material_term" class="form-control form-control-sm" value="{{ $material_term }}" autocomplete="off" oninput="this.value = this.value.toUpperCase()">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Part</label>
                                <input list="parts" name="part_term" class="form-control form-control-sm" value="{{ $part_term ?? '' }}" autocomplete="off" oninput="this.value = this.value.toUpperCase()">
                                <datalist id="parts">
                                    @foreach($parts as $part)
                                        <option value="{{ $part }}">
                                    @endforeach
                                </datalist>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="card-footer d-flex justify-content-end gap-2">
                    @if ($mode == 'details')
                        <button type="button" id="bulkDeleteBtn" class="btn btn-danger shadow-sm">
                            <i class="fas fa-trash me-1"></i> Bulk Delete Selected
                        </button>
                    @endif
                    
                    <input type="hidden" name="mode" value="{{ $mode }}">
                    @foreach($raw_selections as $p)
                        <input type="hidden" name="pivot_months[]" value="{{ $p }}">
                    @endforeach
                    
                    <button type="submit" class="btn btn-success shadow">
                        <i class="fas fa-search me-1"></i> Apply Filters
                    </button>
                    <a href="{{ route('items.index') }}" class="btn btn-outline-secondary shadow">
                        <i class="fas fa-undo me-1"></i> Reset Filter
                    </a>
                </div>
            </div>
        </div>
    </form>

    <form id="bulkDeleteForm" method="POST" action="{{ route('items.bulkDestroy') }}" style="display:none;">
        @csrf
        <div id="bulkDeleteIdsContainer"></div>
    </form>

    <div class="d-flex mb-3 gap-2">
        <a href="{{ route('items.index', array_merge(request()->query(), ['mode' => 'resume'])) }}" class="btn {{ $mode == 'resume' ? 'btn-info text-white shadow-lg' : 'btn-outline-info' }}">
            <i class="fas fa-table me-1"></i> Resume (Monthly Pivot)
        </a>
        <a href="{{ route('items.index', array_merge(request()->query(), ['mode' => 'details'])) }}" class="btn {{ $mode == 'details' ? 'btn-info text-white shadow-lg' : 'btn-outline-info' }}">
            <i class="fas fa-list-ul me-1"></i> Details (All Records)
        </a>
    </div>

    <div class="card shadow-lg">
        <div class="card-header bg-info text-black">
            @if ($mode == 'details')
                Hasil Data Transaksi - Details (Total {{ $items->count() }} Records)
            @else
                Hasil Data Transaksi - Resume (Total {{ count(collect($summary_rows)->groupBy('material')) }} Materials)
            @endif
        </div>
        <div class="card-body p-0">
            @if (($items->isEmpty() && $mode == 'details') || ($mode == 'resume' && empty($summary_rows)))
                <p class="text-center text-muted p-4">Tidak ada data transaksi yang ditemukan berdasarkan filter yang diterapkan.</p>
            @else
                <div class="table-responsive" style="max-height: 70vh;">
                    @if ($mode == 'details')
                        <table class="table table-bordered table-striped table-hover table-sm mb-0">
                            <thead class="bg-light sticky-top">
                                <tr>
                                    <th style="width:36px"><input type="checkbox" id="select-all-details"></th>
                                    <th class="text-nowrap text-center">Aksi</th>
                                    <th class="text-nowrap">Tanggal</th>
                                    <th class="text-nowrap">Material</th>
                                    <th class="text-nowrap">Part</th>
                                    <th class="text-nowrap">No Lot</th>
                                    <th class="text-nowrap">Kode</th>
                                    <th class="text-nowrap text-end">Berat Mentah</th>
                                    <th class="text-nowrap text-end">Goods</th>
                                    <th class="text-nowrap text-end">Scrap</th>
                                    <th class="text-nowrap text-end">Cakalan</th>
                                    <th class="text-nowrap text-end">Deficit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $item)
                                    @php
                                        $deficit = $item->berat_mentah - $item->gkg - $item->scrap - $item->cakalan;
                                    @endphp
                                    <tr>
                                        <td><input type="checkbox" class="select-detail" name="selected_ids[]" value="{{ $item->id }}"></td>
                                        <td class="text-nowrap text-center">
                                            <a href="{{ route('items.edit', $item->id) }}" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                        </td>
                                        <td class="text-nowrap">{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}</td>
                                        <td class="text-nowrap">{{ $item->material }}</td>
                                        <td class="text-nowrap">{{ $item->part }}</td>
                                        <td class="text-nowrap">{{ $item->no_lot }}</td>
                                        <td class="text-nowrap">{{ $item->kode }}</td>
                                        <td class="text-end font-monospace">{{ number_format($item->berat_mentah, 2) }}</td>
                                        <td class="text-end font-monospace">{{ number_format($item->gkg, 2) }}</td>
                                        <td class="text-end font-monospace">{{ number_format($item->scrap, 2) }}</td>
                                        <td class="text-end font-monospace">{{ number_format($item->cakalan, 2) }}</td>
                                        <td class="text-end font-monospace fw-bold {{ $deficit != 0 ? 'text-danger' : 'text-success' }}">
                                            {{ number_format($deficit, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @elseif ($mode == 'resume')
                        <table class="table table-bordered table-striped table-hover table-sm mb-0">
                            <thead class="bg-light sticky-top">
                                <tr>
                                    <th class="text-nowrap">Material</th>
                                    @if (count($months) > 0)
                                        @foreach($months as $m)
                                            <th class="text-nowrap text-center" style="min-width:80px;">{{ $m['label'] }}</th>
                                        @endforeach
                                    @endif
                                    <th class="text-nowrap text-center" style="min-width:90px;">Total GKG</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($summary_rows as $row)
                                    @php
                                        $itemKey = $row['material'];
                                    @endphp
                                    <tr class="item-master-row" data-item-key="{{ $itemKey }}" data-id-list="{{ $row['row_ids'] }}" style="cursor: pointer;">
                                        <td class="fw-bold">{{ $row['material'] }}</td>
                                        
                                        @if (count($months) > 0)
                                            @foreach($months as $m)
                                                @php
                                                    $val = $row['months'][$m['key']] ?? 0;
                                                @endphp
                                                <td class="text-end font-monospace {{ $val < 0 ? 'text-danger' : 'text-success' }}">
                                                    {{ number_format($val, 0, ',', '.') }}
                                                </td>
                                            @endforeach
                                        @endif
                                        
                                        <td class="text-end fw-bold font-monospace bg-light">
                                            {{ number_format($row['total_gkg'], 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="pivotDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl"> 
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Detail Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="detail-loading" class="text-center p-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2">Memuat data...</p>
                    </div>
                    <div id="detail-content" style="display: none;">
                        <h5 id="detail-item-info" class="mb-3"></h5>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-sm" id="modalDetailTable">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Material</th>
                                        <th>Part</th>
                                        <th>No Lot</th>
                                        <th>Kode</th>
                                        <th class="text-end">GKG</th>
                                        <th class="text-end">Scrap</th>
                                    </tr>
                                </thead>
                                <tbody id="detail-table-body"></tbody>
                                <tfoot class="bg-light fw-bold">
                                    <tr>
                                        <td colspan="5" class="text-end">Total</td>
                                        <td class="text-end" id="modal-total-gkg"></td>
                                        <td class="text-end" id="modal-total-scrap"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content border-danger">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i> Konfirmasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <p class="mb-2 fw-bold text-danger">Hapus data ini?</p>
                    <p class="mb-0 text-dark fw-bold" id="deleteRecordDesc"></p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger btn-sm" id="confirmDeleteBtn">Hapus</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function() {
    const selectedPivot = @json($raw_selections ?? []);
    
    (function syncFromServerPivot() {
        const yearlyYears = [];
        const monthlyYears = [];
        const monthVals = [];
        let yearlyMode = 'total';

        selectedPivot.forEach(function(p) {
            if (String(p).startsWith('YEARLY-')) {
                const parts = String(p).replace('YEARLY-','').split('|');
                yearlyYears.push(parts[0]);
                if (parts.length > 1) yearlyMode = parts[1];
            } else if (/^\d{4}-\d{2}$/.test(String(p))) {
                monthVals.push(String(p));
            }
        });

        yearlyYears.forEach(y => $('#year_yearly_' + y).prop('checked', true));
        $('#yearlyMode').val(yearlyMode);
        
        monthVals.forEach(m => {
            const year = m.slice(0,4);
            if (!monthlyYears.includes(year)) monthlyYears.push(year);
            $('#month_' + m).prop('checked', true);
        });
        
        monthlyYears.forEach(y => $('#year_monthly_' + y).prop('checked', true));
        
        updateYearsLabel('.yearly-year-checkbox', '#yearlyYearsLabel');
        updateYearsLabel('.monthly-year-checkbox', '#monthlyYearsLabel');
        syncMonthlyGroupsVisibility();
        updateMonthsCount();
    })();

    function updateYearsLabel(selector, labelId) {
        const checked = $(selector + ':checked');
        let label = 'Pilih Tahun';
        if (checked.length === 1) label = checked.val();
        else if (checked.length > 1) label = checked.length + ' Tahun terpilih';
        $(labelId).text(label);
    }
    
    function syncMonthlyGroupsVisibility() {
        const selYears = $('.monthly-year-checkbox:checked').map(function(){ return $(this).val(); }).get() || [];
        $('.monthly-year-group').each(function(){
            const y = $(this).data('year') + '';
            if (selYears.indexOf(y) !== -1) $(this).show();
            else $(this).hide().find('.monthly-month-checkbox').prop('checked', false);
        });
    }

    function updateMonthsCount() {
        $('#months-selected-count').text($('.monthly-month-checkbox:checked').length + ' Bulan terpilih');
    }

    function rebuildPivotHiddenInputs() {
        $('input[name="pivot_months[]"]', '#filterForm').remove();
        const yearlyYears = $('.yearly-year-checkbox:checked').map(function(){ return $(this).val(); }).get();
        const yearlyMode = $('#yearlyMode').val() || 'total';
        
        yearlyYears.forEach(y => {
            $('<div>').html('<input type="hidden" name="pivot_months[]" value="YEARLY-' + y + '|' + yearlyMode + '">').children().appendTo('#filterForm');
        });

        const monthly = $('.monthly-month-checkbox:checked').map(function(){ return $(this).val(); }).get();
        monthly.forEach(ym => {
            $('<div>').html('<input type="hidden" name="pivot_months[]" value="' + ym + '">').children().appendTo('#filterForm');
        });
    }

    $('.yearly-year-checkbox').on('change', function() {
        updateYearsLabel('.yearly-year-checkbox', '#yearlyYearsLabel');
        rebuildPivotHiddenInputs();
    });
    
    $('.monthly-year-checkbox').on('change', function(){
        updateYearsLabel('.monthly-year-checkbox', '#monthlyYearsLabel');
        syncMonthlyGroupsVisibility();
        rebuildPivotHiddenInputs();
    });
    
    $(document).on('change', '.monthly-month-checkbox, #yearlyMode', function(){
        rebuildPivotHiddenInputs();
        updateMonthsCount();
    });
    
    $('.dropdown-menu').on('click', function (e) { e.stopPropagation(); });

    $('.item-master-row').on('click', function() {
        const idList = $(this).data('id-list');
        const itemKey = $(this).data('item-key');
        
        $('#detail-content').hide();
        $('#detail-loading').show();
        $('#pivotDetailModal').modal('show');
        
        $.ajax({
            url: '{{ route("items.index") }}',
            data: { action: 'pivot_row_details', id_list: idList, item_key: itemKey },
            success: function(res) {
                $('#detail-item-info').text('Detail: ' + res.item_key.replace('||', ' - '));
                
                let rows = '';
                res.details.forEach(d => {
                    rows += `<tr>
                        <td>${d.tanggal}</td>
                        <td>${d.material}</td>
                        <td>${d.part || '-'}</td>
                        <td>${d.no_lot || '-'}</td>
                        <td>${d.kode || '-'}</td>
                        <td class="text-end">${parseFloat(d.gkg).toLocaleString()}</td>
                        <td class="text-end">${parseFloat(d.scrap).toLocaleString()}</td>
                    </tr>`;
                });
                
                $('#detail-table-body').html(rows);
                $('#modal-total-gkg').text(parseFloat(res.total_qty).toLocaleString());
                $('#modal-total-scrap').text(parseFloat(res.total_scrap).toLocaleString());
                
                $('#detail-loading').hide();
                $('#detail-content').show();
            }
        });
    });

    $('#select-all-details').on('change', function() { $('.select-detail').prop('checked', $(this).is(':checked')); });
    
    $('#bulkDeleteBtn').on('click', function() {
        const selected = $('.select-detail:checked').map(function(){ return $(this).val(); }).get();
        if (selected.length === 0) return alert('Pilih data untuk dihapus.');
        
        $('#bulkDeleteIdsContainer').empty();
        selected.forEach(val => $('<input>').attr({ type: 'hidden', name: 'selected_ids[]', value: val }).appendTo('#bulkDeleteIdsContainer'));
        
        $('#deleteRecordDesc').text(`Total ${selected.length} records.`);
        $('#deleteConfirmModal').modal('show');
    });
    
    $('#confirmDeleteBtn').on('click', function() {
        $('#bulkDeleteForm').submit();
    });
});
</script>
@endsection