@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-primary">
                <div class="card-header bg-primary text-white fw-bold">
                    <h5 class="mb-0">Input Mutasi Barang</h5>
                </div>
                <form action="{{ route('mutations.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tanggal</label>
                                <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Material (Sumber: Items)</label>
                                <select name="material" id="input_material" class="form-select" required>
                                    <option value="">-- Pilih Material --</option>
                                    @foreach($materials as $m)
                                        <option value="{{ $m }}">{{ $m }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Part (Sumber: Items)</label>
                            <select name="part" id="input_part" class="form-select" required disabled>
                                <option value="">-- Pilih Material Dulu --</option>
                            </select>
                        </div>

                        <hr>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Tipe Barang</label>
                                <select name="tipe_barang" id="input_type" class="form-select" required>
                                    <option value="gkg">Barang Jadi (GKG)</option>
                                    <option value="scrap">Scrap</option>
                                    <option value="cakalan">Cakalan</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-success">Sisa Stock (Items)</label>
                                <input type="text" id="display_stock" class="form-control bg-secondary text-white fw-bold font-monospace" value="0.00" readonly tabindex="-1">
                                <small class="text-muted d-block mt-1">*Data dari page Produksi</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Kuantitas (KG)</label>
                                <input type="number" step="0.01" name="berat" class="form-control" required placeholder="0.00">
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a href="{{ route('mutations.index') }}" class="btn btn-secondary me-2">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Mutasi</button>
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
        const typeSelect = $('#input_type');
        const stockDisplay = $('#display_stock');

        function fetchStock() {
            const mat = matSelect.val();
            const part = partSelect.val();
            const type = typeSelect.val();

            if (mat && part && type) {
                stockDisplay.val('Checking...');
                $.ajax({
                    url: '{{ route("mutations.index") }}',
                    data: { action: 'check_stock', material: mat, part: part, type: type },
                    success: function(res) {
                        stockDisplay.val(res.stock);
                    },
                    error: function() { stockDisplay.val('Error'); }
                });
            } else {
                stockDisplay.val('0.00');
            }
        }

        matSelect.change(function() {
            const mat = $(this).val();
            partSelect.html('<option value="">Loading...</option>').prop('disabled', true);
            stockDisplay.val('0.00');

            if (mat) {
                $.ajax({
                    url: '{{ route("mutations.index") }}',
                    data: { action: 'get_parts', material: mat },
                    success: function(res) {
                        let html = '<option value="">-- Pilih Part --</option>';
                        res.forEach(function(p) { html += `<option value="${p}">${p}</option>`; });
                        partSelect.html(html).prop('disabled', false);
                    }
                });
            } else {
                partSelect.html('<option value="">-- Pilih Material Dulu --</option>').prop('disabled', true);
            }
        });

        partSelect.change(fetchStock);
        typeSelect.change(fetchStock);
    });
</script>
@endsection