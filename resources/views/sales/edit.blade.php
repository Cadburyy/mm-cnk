@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-warning">
                <div class="card-header bg-warning text-dark fw-bold">
                    <h5 class="mb-0">Edit Mutasi Penjualan</h5>
                </div>
                <form action="{{ route('sales.update', $item->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tanggal</label>
                                <input type="date" name="tanggal" class="form-control" value="{{ $item->tanggal->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Customer (Tujuan)</label>
                                <input type="text" name="customer" class="form-control text-uppercase" value="{{ $item->customer }}" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Material</label>
                                <select name="material" id="input_material" class="form-select" required>
                                    <option value="">-- Pilih Material --</option>
                                    @foreach($materials as $m)
                                        <option value="{{ $m }}" {{ $m == $item->material ? 'selected' : '' }}>{{ $m }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Part</label>
                                <select name="part" id="input_part" class="form-select" required>
                                    <option value="{{ $item->part }}" selected>{{ $item->part }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Tipe Barang</label>
                                <input type="text" class="form-control bg-light" value="{{ $item->scrap > 0 ? 'Scrap' : ($item->cakalan > 0 ? 'Cakalan' : 'Barang Jadi (GKG)') }}" readonly>
                                <input type="hidden" id="input_type" value="{{ $item->scrap > 0 ? 'scrap' : ($item->cakalan > 0 ? 'cakalan' : 'gkg') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-success">Sisa Stock (KG)</label>
                                <input type="text" id="display_stock" class="form-control bg-secondary text-white fw-bold font-monospace" value="Loading..." readonly tabindex="-1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Berat (KG)</label>
                                <input type="number" step="0.01" name="berat" class="form-control" value="{{ $item->scrap > 0 ? $item->scrap : ($item->cakalan > 0 ? $item->cakalan : $item->gkg) }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a href="{{ route('sales.index') }}" class="btn btn-secondary me-2">Batal</a>
                        <button type="submit" class="btn btn-warning">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        const matSelect = $('#input_material');
        const partSelect = $('#input_part');
        const typeVal = $('#input_type').val();
        const stockDisplay = $('#display_stock');

        function fetchStock() {
            const mat = matSelect.val();
            const part = partSelect.val();

            if (mat && part) {
                stockDisplay.val('Checking...');
                $.ajax({
                    url: '{{ route("sales.index") }}',
                    data: { action: 'check_stock', material: mat, part: part, type: typeVal },
                    success: function(res) {
                        stockDisplay.val(res.stock);
                    }
                });
            }
        }

        matSelect.change(function() {
            const mat = $(this).val();
            partSelect.html('<option value="">Loading...</option>').prop('disabled', true);
            stockDisplay.val('0.00');

            if (mat) {
                $.ajax({
                    url: '{{ route("sales.index") }}',
                    data: { action: 'get_parts', material: mat },
                    success: function(res) {
                        let html = '<option value="">-- Pilih Part --</option>';
                        res.forEach(function(p) { html += `<option value="${p}">${p}</option>`; });
                        partSelect.html(html).prop('disabled', false);
                    }
                });
            }
        });

        partSelect.change(fetchStock);
        fetchStock();
    });
</script>
@endsection