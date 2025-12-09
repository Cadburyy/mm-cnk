@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Edit Data Produksi #{{ $item->id }}</h5>
                </div>
                <form action="{{ route('items.update', $item->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        {{-- Row 1: Tanggal & Material --}}
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tanggal</label>
                                <input type="date" name="tanggal" class="form-control @error('tanggal') is-invalid @enderror" value="{{ old('tanggal', $item->tanggal ? $item->tanggal->format('Y-m-d') : '') }}" required>
                                @error('tanggal')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Material</label>
                                {{-- Added list, id, autocomplete, and oninput to match create.blade.php --}}
                                <input type="text" name="material" id="input_material" class="form-control text-uppercase @error('material') is-invalid @enderror" value="{{ old('material', $item->material) }}" required autocomplete="off" oninput="this.value = this.value.toUpperCase()" list="material_options" placeholder="Type or select material...">
                                <datalist id="material_options">
                                    {{-- Populated by JS --}}
                                </datalist>
                                @error('material')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Row 2: Part, No Lot, Kode --}}
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Part</label>
                                {{-- Added list, id, autocomplete, and oninput to match create.blade.php --}}
                                <input type="text" name="part" id="input_part" class="form-control text-uppercase @error('part') is-invalid @enderror" value="{{ old('part', $item->part) }}" autocomplete="off" oninput="this.value = this.value.toUpperCase()" list="part_options" placeholder="Type or select part...">
                                <datalist id="part_options">
                                    {{-- Populated by JS --}}
                                </datalist>
                                @error('part')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">No Lot</label>
                                <input type="text" name="no_lot" class="form-control text-uppercase @error('no_lot') is-invalid @enderror" value="{{ old('no_lot', $item->no_lot) }}" autocomplete="off" oninput="this.value = this.value.toUpperCase()">
                                @error('no_lot')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Kode</label>
                                <input type="text" name="kode" class="form-control text-uppercase @error('kode') is-invalid @enderror" value="{{ old('kode', $item->kode) }}" autocomplete="off" oninput="this.value = this.value.toUpperCase()">
                                @error('kode')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr>
                        <h6 class="text-primary fw-bold mb-3">Detail Kuantitas (Opsional)</h6>

                        {{-- Row 3: Quantities --}}
                        <div class="row mb-3">
                            <div class="col-md-2">
                                <label class="form-label">Berat Mentah</label>
                                <input type="number" step="0.001" name="berat_mentah" class="form-control @error('berat_mentah') is-invalid @enderror" value="{{ old('berat_mentah', $item->berat_mentah) }}" autocomplete="off">
                                @error('berat_mentah')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">GPCS</label>
                                {{-- Added id to match create.blade.php for calculation --}}
                                <input type="number" step="0.001" name="gpcs" id="input_gpcs" class="form-control @error('gpcs') is-invalid @enderror" value="{{ old('gpcs', $item->gpcs) }}" autocomplete="off">
                                @error('gpcs')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            {{-- G. Weight Input (Auto-filled from DB via JS) --}}
                            <div class="col-md-2">
                                <label class="form-label text-muted">G. Weight (Gram)</label>
                                <input type="number" step="0.0001" id="input_gweight" class="form-control bg-light" placeholder="Auto-filled" readonly tabindex="-1">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">GKG (Total KG)</label>
                                {{-- Added id and readonly to match create.blade.php --}}
                                <input type="number" step="0.001" name="gkg" id="input_gkg" class="form-control @error('gkg') is-invalid @enderror" value="{{ old('gkg', $item->gkg) }}" autocomplete="off" readonly>
                                @error('gkg')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Scrap</label>
                                <input type="number" step="0.001" name="scrap" class="form-control @error('scrap') is-invalid @enderror" value="{{ old('scrap', $item->scrap) }}" autocomplete="off">
                                @error('scrap')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Cakalan</label>
                                <input type="number" step="0.001" name="cakalan" class="form-control @error('cakalan') is-invalid @enderror" value="{{ old('cakalan', $item->cakalan) }}" autocomplete="off">
                                @error('cakalan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a href="{{ route('items.index') }}" class="btn btn-secondary me-2">Batal</a>
                        <button type="submit" class="btn btn-warning">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Load weights data passed from Controller
        // Note: Controller must pass 'weights' variable: return view('items.edit', compact('item', 'weights'));
        const weightsData = @json($weights ?? []); 

        // DOM Elements
        const materialInput = document.getElementById('input_material');
        const partInput = document.getElementById('input_part');
        const materialList = document.getElementById('material_options');
        const partList = document.getElementById('part_options');
        
        const gpcsInput = document.getElementById('input_gpcs');
        const gweightInput = document.getElementById('input_gweight');
        const gkgInput = document.getElementById('input_gkg');

        // 2. Initialize Material List (Unique Values)
        const materials = [...new Set(weightsData.map(w => w.material))];
        materials.sort().forEach(mat => {
            const option = document.createElement('option');
            option.value = mat;
            materialList.appendChild(option);
        });

        // 3. Logic: Filter Parts based on selected Material
        function updatePartOptions() {
            const currentMaterial = materialInput.value.toUpperCase();
            
            // Clear current options
            partList.innerHTML = '';
            
            // Filter weights matching the selected material
            const relevantWeights = weightsData.filter(w => w.material === currentMaterial);
            
            // Extract unique parts for this material
            const parts = [...new Set(relevantWeights.map(w => w.part))];
            
            parts.sort().forEach(part => {
                const option = document.createElement('option');
                option.value = part;
                partList.appendChild(option);
            });
        }

        // 4. Logic: Find Weight based on Material + Part combination
        function findWeight() {
            const currentMaterial = materialInput.value.toUpperCase();
            const currentPart = partInput.value.toUpperCase();
            
            const found = weightsData.find(w => w.material === currentMaterial && w.part === currentPart);
            
            if (found) {
                gweightInput.value = found.weight;
                calculateGkg(); // Recalculate GKG immediately to ensure consistency
            } else {
                gweightInput.value = ''; 
                // We do NOT clear GKG on edit if weight is missing, to preserve stored value
            }
        }

        // 5. Existing GKG Calculation Logic
        function calculateGkg() {
            const pcs = parseFloat(gpcsInput.value) || 0;
            const weight = parseFloat(gweightInput.value) || 0;
            
            if (pcs > 0 && weight > 0) {
                // Formula: (GPCS * Weight_in_Grams) / 1000 = Total KG
                const total = (pcs * weight) / 1000;
                gkgInput.value = total.toFixed(3); 
            }
        }

        // Event Listeners
        materialInput.addEventListener('input', () => {
            updatePartOptions();
            findWeight();
        });
        materialInput.addEventListener('change', () => {
            updatePartOptions();
            findWeight();
        });
        
        partInput.addEventListener('input', findWeight);
        partInput.addEventListener('change', findWeight);

        if (gpcsInput && gweightInput && gkgInput) {
            gpcsInput.addEventListener('input', calculateGkg);
        }

        // --- Initialization for Edit Mode ---
        // Populate options and find weight based on the existing DB values on load
        if (materialInput.value) {
            updatePartOptions(); // Populate part datalist based on item material
            findWeight();        // Find weight and populate G.Weight input
        }
    });
</script>
@endsection