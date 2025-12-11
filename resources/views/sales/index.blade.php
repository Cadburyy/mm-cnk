@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-dark">💰 Data Penjualan Barang</h1>
        <div>
            <a href="{{ route('sales.create') }}" class="btn btn-primary shadow-sm">
                <i class="fas fa-plus me-1"></i> Input Penjualan
            </a>
        </div>
    </div>

    @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if (session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    <form method="GET" action="{{ route('sales.index') }}" class="row mb-4" id="filterForm">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light fw-bold">Filter Data</div>
                <div class="card-body">
                    @if ($mode == 'details')
                         <div class="row g-3 mb-2">
                            <div class="col-lg-3 col-md-6"><label class="form-label fw-bold">Start Date</label><input type="date" name="start_date" value="{{ $start_date }}" class="form-control"></div>
                            <div class="col-lg-3 col-md-6"><label class="form-label fw-bold">End Date</label><input type="date" name="end_date" value="{{ $end_date }}" class="form-control"></div>
                            <div class="col-lg-3 col-md-6"><label class="form-label">Material</label><input list="materials" name="material_term" class="form-control" value="{{ $material_term }}" oninput="this.value = this.value.toUpperCase()"><datalist id="materials">@foreach($materials as $mat)<option value="{{ $mat }}">@endforeach</datalist></div>
                            <div class="col-lg-3 col-md-6"><label class="form-label">Part</label><input list="parts" name="part_term" class="form-control" value="{{ $part_term }}" oninput="this.value = this.value.toUpperCase()"><datalist id="parts">@foreach($parts as $part)<option value="{{ $part }}">@endforeach</datalist></div>
                        </div>
                    @else
                        <div class="row g-3">
                            <div class="col-lg-6 col-md-12">
                                <label class="form-label fw-bold">Yearly</label>
                                <div class="card p-3 h-100">
                                    <div class="mb-2">
                                        <div class="dropdown" id="yearlyYearsDropdown">
                                            <button class="btn btn-outline-secondary btn-sm w-100 text-start dropdown-toggle" type="button" id="yearlyYearsBtn" data-bs-toggle="dropdown" aria-expanded="false"><span id="yearlyYearsLabel">Pilih Tahun</span></button>
                                            <div class="dropdown-menu w-100 p-2" style="max-height:200px; overflow-y:auto;" aria-labelledby="yearlyYearsBtn">
                                                <input type="hidden" name="yearly_years[]" id="yearlyYearsHidden">
                                                @foreach($distinctYears as $year)
                                                    <div class="form-check mb-2"><input class="form-check-input yearly-year-checkbox" type="checkbox" id="year_yearly_{{ $year }}" value="{{ $year }}"><label class="form-check-label small" for="year_yearly_{{ $year }}">{{ $year }}</label></div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-2"><label class="form-label small mb-1">Mode</label><select id="yearlyMode" class="form-control form-control-sm" name="yearly_mode"><option value="total">Total</option></select></div>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <label class="form-label fw-bold">Monthly</label>
                                <div class="card p-3 h-100">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="dropdown"><button class="btn btn-outline-secondary btn-sm w-100 text-start dropdown-toggle" type="button" id="monthlyYearsBtn" data-bs-toggle="dropdown"><span id="monthlyYearsLabel">Pilih Tahun</span></button>
                                            <div class="dropdown-menu w-100 p-2" style="max-height:200px; overflow-y:auto;">@foreach($distinctYears as $year)<div class="form-check mb-2"><input class="form-check-input monthly-year-checkbox" type="checkbox" id="year_monthly_{{ $year }}" value="{{ $year }}"><label class="form-check-label small">{{ $year }}</label></div>@endforeach</div></div>
                                        </div>
                                        <div class="col-7">
                                            <div id="monthlyMonthsContainer" class="p-1" style="max-height:160px; overflow:auto; border:1px solid #e9ecef; border-radius:4px;">
                                                @foreach($distinctYearMonths as $yr => $mList)
                                                    <div class="monthly-year-group mb-2" data-year="{{ $yr }}" style="display:none;"><div class="fw-bold small mb-1">{{ $yr }}</div><div class="d-flex flex-wrap gap-2">@foreach($mList as $ym)<div class="form-check"><input class="form-check-input monthly-month-checkbox" type="checkbox" id="month_{{ $ym }}" value="{{ $ym }}"><label class="form-check-label" for="month_{{ $ym }}">{{ \Carbon\Carbon::createFromFormat('Y-m', $ym)->format('M') }}</label></div>@endforeach</div></div>
                                                @endforeach
                                            </div>
                                            <div class="small text-muted mt-1"><span id="months-selected-count">0 Bulan terpilih</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                         <div class="row g-3 mt-4">
                            <div class="col-6"><label class="form-label">Material</label><input list="materials" name="material_term" class="form-control" value="{{ $material_term }}" oninput="this.value = this.value.toUpperCase()"><datalist id="materials">@foreach($materials as $mat)<option value="{{ $mat }}">@endforeach</datalist></div>
                            <div class="col-6"><label class="form-label">Part</label><input list="parts" name="part_term" class="form-control" value="{{ $part_term }}" oninput="this.value = this.value.toUpperCase()"><datalist id="parts">@foreach($parts as $part)<option value="{{ $part }}">@endforeach</datalist></div>
                        </div>
                    @endif
                </div>
                <div class="card-footer d-flex justify-content-end gap-2">
                    @if ($mode == 'details') <button type="button" id="bulkDeleteBtn" class="btn btn-danger shadow-sm">Delete Selected</button> @endif
                    <input type="hidden" name="mode" value="{{ $mode }}">
                    @foreach($raw_selections as $p) <input type="hidden" name="pivot_months[]" value="{{ $p }}"> @endforeach
                    <button type="submit" class="btn btn-success shadow"><i class="fas fa-search me-1"></i>Cari</button>
                    <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary shadow"><i class="fas fa-redo"></i> Reset</a>
                </div>
            </div>
        </div>
    </form>
    
    <form id="bulkDeleteForm" method="POST" action="{{ route('sales.bulkDestroy') }}" style="display:none;">@csrf<div id="bulkDeleteIdsContainer"></div></form>

    <div class="d-flex mb-3 gap-2">
        <a href="{{ route('sales.index', array_merge(request()->query(), ['mode' => 'resume'])) }}" class="btn {{ $mode == 'resume' ? 'btn-info text-white shadow-lg' : 'btn-outline-info' }}">Resume (Net Stock)</a>
        <a href="{{ route('sales.index', array_merge(request()->query(), ['mode' => 'details'])) }}" class="btn {{ $mode == 'details' ? 'btn-info text-white shadow-lg' : 'btn-outline-info' }}">Details (Records)</a>
    </div>

    <div class="card shadow-lg">
        <div class="card-header bg-info text-black">{{ $mode == 'details' ? 'Hasil Data - Details' : 'Hasil Data - Net Stock (Total Penjualan)' }}</div>
        <div class="card-body p-0">
            @if (($items->isEmpty() && $mode == 'details') || ($mode == 'resume' && empty($summary_tree)))
                <p class="text-center text-muted p-4">Tidak ada data ditemukan.</p>
            @else
                <div class="table-responsive" style="max-height: 70vh;">
                    @if ($mode == 'details')
                        <table class="table table-bordered table-striped table-hover table-sm mb-0">
                            <thead class="bg-light sticky-top">
                                <tr>
                                    <th><input type="checkbox" id="select-all-details"></th><th class="text-center">Aksi</th><th>Tanggal</th><th>Customer</th><th>Material</th><th>Part</th><th>Tipe</th><th class="text-end">Berat (KG)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $item)
                                    <tr>
                                        <td><input type="checkbox" class="select-detail" name="selected_ids[]" value="{{ $item->id }}"></td>
                                        <td class="text-center">
                                            <a href="{{ route('sales.edit', $item->id) }}" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                        </td>
                                        <td>{{ $item->tanggal->format('d/m/Y') }}</td>
                                        <td class="fw-bold text-primary">{{ $item->customer }}</td>
                                        <td>{{ $item->material }}</td><td>{{ $item->part }}</td>
                                        <td>
                                            @if($item->gkg > 0) <span class="badge bg-success">GKG</span>
                                            @elseif($item->scrap > 0) <span class="badge bg-warning text-dark">Scrap</span>
                                            @elseif($item->cakalan > 0) <span class="badge bg-secondary">Cakalan</span>
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold">{{ number_format($item->gkg + $item->scrap + $item->cakalan, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @elseif ($mode == 'resume')
                        <table class="table table-bordered table-striped table-hover table-sm mb-0">
                            <thead class="bg-light sticky-top">
                                <tr>
                                    <th style="width:30px;" class="text-center">+/-</th>
                                    <th class="text-nowrap bg-primary text-white">Description</th>
                                    @if (count($months) > 0)
                                        @foreach($months as $m) <th class="text-nowrap text-center" style="min-width:80px;">{{ $m['label'] }}</th> @endforeach
                                    @endif
                                    <th class="text-nowrap text-center" style="min-width:90px;">Total (All)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($summary_tree as $material => $matData)
                                    @php $matUniqueId = md5($material); $matIds = implode(',', array_unique($matData['ids'])); @endphp
                                    <tr class="resume-row parent-row" style="background-color: #f0f0f0; cursor: pointer;" data-id-list="{{ $matIds }}" data-metric="mix" data-level="material" data-name="{{ $material }}">
                                        <td class="text-center stop-propagation">
                                            <button type="button" class="btn btn-sm btn-outline-secondary toggle-btn" data-target=".child-mat-{{ $matUniqueId }}"><i class="fas fa-plus"></i></button>
                                        </td>
                                        <td class="fw-bold text-primary">{{ $material }}</td>
                                        @if (count($months) > 0)
                                            @foreach($months as $m) 
                                                @php $val = $matData['months_all'][$m['key']] ?? 0; @endphp
                                                <td class="text-end font-monospace {{ $val < 0 ? 'text-danger fw-bold' : '' }}">{{ number_format($val, 0, ',', '.') }}</td> 
                                            @endforeach
                                        @endif
                                        <td class="text-end fw-bold font-monospace bg-light {{ $matData['total_all'] < 0 ? 'text-danger' : '' }}">{{ number_format($matData['total_all'], 0, ',', '.') }}</td>
                                    </tr>
                                    @foreach($matData['parts'] as $part => $partData)
                                        @php $partUniqueId = md5($material . $part); $partIds = implode(',', array_unique($partData['ids'])); @endphp
                                        <tr class="child-mat-{{ $matUniqueId }} parent-row" style="display:none; background-color: #fff; cursor:pointer;" data-id-list="{{ $partIds }}" data-metric="mix" data-level="part" data-name="{{ $part }}">
                                            <td class="text-center stop-propagation">
                                                 <button type="button" class="btn btn-sm btn-light border btn-xs ms-2 toggle-btn" data-target=".child-part-{{ $partUniqueId }}"><i class="fas fa-plus fa-xs"></i></button>
                                            </td>
                                            <td class="ps-4 fw-bold text-dark"><i class="fas fa-cube me-1 text-muted"></i> {{ $part }}</td>
                                            @if (count($months) > 0)
                                                @foreach($months as $m) 
                                                    @php $val = $partData['months_all'][$m['key']] ?? 0; @endphp
                                                    <td class="text-end font-monospace small {{ $val < 0 ? 'text-danger fw-bold' : '' }}">{{ number_format($val, 0, ',', '.') }}</td> 
                                                @endforeach
                                            @endif
                                            <td class="text-end font-monospace fw-bold small {{ $partData['total_all'] < 0 ? 'text-danger' : '' }}">{{ number_format($partData['total_all'], 0, ',', '.') }}</td>
                                        </tr>
                                        <!-- BREAKDOWN ROWS -->
                                        @foreach(['gkg' => 'GKG', 'scrap' => 'Scrap', 'cakalan' => 'Cakalan'] as $metricKey => $label)
                                            @if(($partData['total_'.$metricKey] ?? 0) != 0)
                                            <tr class="child-part-{{ $partUniqueId }}" style="display:none; background-color: #fdfdfd;">
                                                <td></td>
                                                <td class="ps-5 text-dark small"><i class="fas fa-arrow-right me-1"></i> {{ $label }}</td>
                                                @if (count($months) > 0)
                                                    @foreach($months as $m)
                                                        @php $val = $partData['months_'.$metricKey][$m['key']] ?? 0; @endphp
                                                        <td class="text-end font-monospace small text-muted {{ $val < 0 ? 'text-danger fw-bold' : '' }}">{{ number_format($val, 0, ',', '.') }}</td> 
                                                    @endforeach
                                                @endif
                                                <td class="text-end font-monospace small {{ $partData['total_'.$metricKey] < 0 ? 'text-danger' : '' }}">{{ number_format($partData['total_'.$metricKey], 0, ',', '.') }}</td>
                                            </tr>
                                            @endif
                                        @endforeach
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="pivotDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl"> 
            <div class="modal-content">
                <div class="modal-header bg-info text-white"><h5 class="modal-title">Detail Penjualan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div id="detail-loading" class="text-center p-5"><div class="spinner-border text-primary"></div><p>Memuat data...</p></div>
                    <div id="detail-content" style="display: none;">
                        <div class="mb-3"></div>
                        <div id="monthly-subtotals-container" class="mb-3 p-3 bg-white border rounded" style="display:none;">
                            <h6 class="fw-bold mb-2 text-info"><i class="fas fa-calendar-alt me-1"></i> Monthly Subtotals (Filtered)</h6>
                            <div id="monthly-subtotals-list" class="d-flex flex-wrap gap-3"></div>
                        </div>
                        <div class="table-responsive" style="max-height: 50vh;"><div id="detail-table-container"></div></div>
                        <div class="mt-3 text-end border-top pt-2"><h5>Total Net Stock: <span id="modal-metric-total" class="fw-bold font-monospace"></span></h5></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-sm"><div class="modal-content border-danger"><div class="modal-header bg-danger text-white"><h5 class="modal-title">Konfirmasi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body text-center"><p class="text-danger fw-bold">Hapus data?</p><p id="deleteRecordDesc"></p></div><div class="modal-footer justify-content-center"><button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button><button class="btn btn-danger btn-sm" id="confirmDeleteBtn">Hapus</button></div></div></div></div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function formatNumberJS(v) { if (v == null || isNaN(v)) return '0'; return parseFloat(v).toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
function formatDateJS(d) { if (!d) return '-'; if (d.indexOf('T')>-1) d=d.split('T')[0]; const p=d.split('-'); if(p.length===3) return `${p[2]}/${p[1]}/${p[0]}`; return d; }
function formatMonthYear(ym) { if (!ym || ym.length!==7) return ym; const [y, m] = ym.split('-'); return new Date(y, m-1, 1).toLocaleDateString('id-ID', {month:'short', year:'numeric'}); }

$(function() {
    const selectedPivot = @json($raw_selections ?? []);
    const mode = '{{ $mode }}';
    
    if (mode === 'resume') {
        $('.yearly-year-checkbox, .monthly-year-checkbox, .monthly-month-checkbox, #yearlyMode').on('change', function() {
             if($(this).hasClass('yearly-year-checkbox')) updateYearsLabel('.yearly-year-checkbox', '#yearlyYearsLabel');
             if($(this).hasClass('monthly-year-checkbox')) { updateYearsLabel('.monthly-year-checkbox', '#monthlyYearsLabel'); syncMonthlyGroupsVisibility(); }
             rebuildPivotHiddenInputs(); updateMonthsCount();
        });
        (function initFilters() {
             selectedPivot.forEach(p => {
                 if(String(p).startsWith('YEARLY-')) $('#year_yearly_' + String(p).replace('YEARLY-','').split('|')[0]).prop('checked', true);
                 else if(/^\d{4}-\d{2}$/.test(String(p))) { $('#month_'+p).prop('checked', true); $('#year_monthly_'+String(p).slice(0,4)).prop('checked', true); }
             });
             updateYearsLabel('.yearly-year-checkbox', '#yearlyYearsLabel'); updateYearsLabel('.monthly-year-checkbox', '#monthlyYearsLabel'); syncMonthlyGroupsVisibility(); updateMonthsCount();
        })();
    }
    function updateYearsLabel(sel, lbl) { const c=$(sel+':checked').length; $(lbl).text(c===0?'Pilih Tahun':(c===1?$(sel+':checked').val():c+' Tahun terpilih')); }
    function updateMonthsCount() { $('#months-selected-count').text($('.monthly-month-checkbox:checked').length + ' Bulan terpilih'); }
    function syncMonthlyGroupsVisibility() { const ys=$('.monthly-year-checkbox:checked').map((_,e)=>$(e).val()).get(); $('.monthly-year-group').each((_,e)=>{ $(e).toggle(ys.includes($(e).data('year')+'')); if(!ys.includes($(e).data('year')+'')) $(e).find('input').prop('checked',false); }); }
    function rebuildPivotHiddenInputs() { $('input[name="pivot_months[]"]', '#filterForm').remove(); $('.yearly-year-checkbox:checked').each((_,e)=>$('<input>').attr({type:'hidden',name:'pivot_months[]',value:'YEARLY-'+$(e).val()+'|'+($('#yearlyMode').val()||'total')}).appendTo('#filterForm')); $('.monthly-month-checkbox:checked').each((_,e)=>$('<input>').attr({type:'hidden',name:'pivot_months[]',value:$(e).val()}).appendTo('#filterForm')); }

    $(document).on('click', '.toggle-btn', function(e) {
        e.stopPropagation();
        const btn = $(this);
        const target = $(btn.data('target'));
        const icon = btn.find('i');
        if (target.is(':visible')) { target.hide(); icon.removeClass('fa-minus').addClass('fa-plus'); } else { target.show(); icon.removeClass('fa-plus').addClass('fa-minus'); }
    });
    $(document).on('click', '.stop-propagation', function(e) { e.stopPropagation(); });

    $(document).on('click', '.parent-row', function(e) {
        if ($(e.target).closest('.toggle-btn').length) return;
        const idList = $(this).data('id-list');
        const metric = $(this).data('metric'); 
        const level = $(this).data('level');
        const rawName = $(this).data('name');
        const pivotSelections = $('input[name="pivot_months[]"]').map((_,e)=>$(e).val()).get();

        $('#pivotDetailModal').modal('show');
        $('#detail-content').hide(); $('#detail-loading').show(); $('#monthly-subtotals-list').empty(); $('#monthly-subtotals-container').hide();

        $.ajax({
            url: '{{ route("sales.index") }}',
            data: { action: 'pivot_row_details', id_list: idList, pivot_months: pivotSelections, metric: metric },
            success: function(res) {
                $('#detail-loading').hide(); $('#detail-content').show();
                let headerTitle = res.item_key;
                let topLabel = 'MATERIAL';
                if (level === 'material') { topLabel = 'MATERIAL'; headerTitle = rawName; }

                $('#detail-content .mb-3:first').html(`<div class="card bg-light border-0"><div class="card-body py-2"><span class="text-muted small text-uppercase fw-bold">${topLabel}</span><div class="fw-bold text-primary fs-5">${headerTitle}</div></div></div>`);
                
                let totalColor = res.total_display < 0 ? 'text-danger' : 'text-success';
                $('#modal-metric-total').removeClass('text-danger text-success').addClass(totalColor).text(formatNumberJS(res.total_display) + ' Kg');

                if (pivotSelections.length > 0 && res.monthly_subtotals && Object.keys(res.monthly_subtotals).length > 0) {
                    let subtotalsHtml = '';
                    for (const ym in res.monthly_subtotals) {
                        let val = res.monthly_subtotals[ym];
                        let color = val < 0 ? 'text-danger' : 'text-dark';
                        subtotalsHtml += `<div class="p-2 border rounded shadow-sm bg-light"><div class="small text-muted fw-bold">${formatMonthYear(ym)}</div><div class="fw-bold font-monospace ${color}">${formatNumberJS(val)}</div></div>`;
                    }
                    $('#monthly-subtotals-list').html(subtotalsHtml); $('#monthly-subtotals-container').show();
                } else { $('#monthly-subtotals-container').hide(); }

                let html = '<div class="text-center text-muted">No details.</div>';
                if(res.details && res.details.length > 0) {
                    let tableHead = '<tr><th>Tanggal</th><th>Mat</th><th>Part</th><th>Type</th><th class="text-end">GKG</th><th class="text-end">Scrap</th><th class="text-end">Cakalan</th></tr>';
                    html = '<table class="table table-sm table-striped table-bordered mb-0"><thead class="bg-white sticky-top">' + tableHead + '</thead><tbody>';
                    res.details.forEach(d => {
                        let total = parseFloat(d.gkg) + parseFloat(d.scrap) + parseFloat(d.cakalan);
                        let isMut = d.transaction_type === 'sale';
                        let displayVal = isMut ? -total : total;
                        
                        let badgeClass = d.transaction_type === 'mutation' ? 'bg-success' : 'bg-warning text-dark';
                        let typeLabel = d.transaction_type === 'mutation' ? 'MUTATION (IN)' : 'SALE (OUT)';
                        
                        let rawGkg = parseFloat(d.gkg) || 0;
                        let rawScrap = parseFloat(d.scrap) || 0;
                        let rawCakalan = parseFloat(d.cakalan) || 0;

                        let dispGkg = isMut ? -rawGkg : rawGkg;
                        let dispScrap = isMut ? -rawScrap : rawScrap;
                        let dispCakalan = isMut ? -rawCakalan : rawCakalan;

                        let textClass = isMut ? 'text-danger fw-bold' : 'text-dark';

                        html += `<tr><td>${formatDateJS(d.tanggal)}</td><td>${d.material}</td><td>${d.part||'-'}</td><td><span class="badge ${badgeClass}">${typeLabel}</span></td><td class="text-end ${textClass}">${formatNumberJS(dispGkg)}</td><td class="text-end ${textClass}">${formatNumberJS(dispScrap)}</td><td class="text-end ${textClass}">${formatNumberJS(dispCakalan)}</td></tr>`;
                    });
                    html += '</tbody></table>';
                }
                $('#detail-table-container').html(html);
            }
        });
    });

    $('#select-all-details').on('change', function() { $('.select-detail').prop('checked', $(this).is(':checked')); });
    $('#bulkDeleteBtn').on('click', function() { const s=$('.select-detail:checked').map((_,e)=>$(e).val()).get(); if(!s.length) return alert('Pilih data.'); $('#bulkDeleteIdsContainer').empty(); s.forEach(v=>$('<input>').attr({type:'hidden',name:'selected_ids[]',value:v}).appendTo('#bulkDeleteIdsContainer')); $('#deleteRecordDesc').text('Total '+s.length+' records.'); $('#deleteConfirmModal').modal('show'); });
    $('#confirmDeleteBtn').on('click', function() { $('#bulkDeleteForm').submit(); });
});
</script>
@endsection