@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-warning">
                <div class="card-header bg-warning text-dark fw-bold h4">
                    <i class="fas fa-edit me-2"></i> Edit Data Budget #{{ $budget->id }}
                </div>
                <form action="{{ route('budget.update', $budget->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <p class="text-muted small">Perhatian: Anda hanya boleh mengedit data budget ini jika Anda yakin informasinya salah.</p>
                        
                        <div class="mb-3">
                            <label for="item_number" class="form-label fw-bold">Item Number</label>
                            <input type="text" name="item_number" id="item_number" class="form-control" value="{{ old('item_number', $budget->item_number) }}" required oninput="this.value = this.value.toUpperCase()">
                        </div>
                        
                        <div class="mb-3">
                            <label for="item_description" class="form-label fw-bold">Item Description</label>
                            <input type="text" name="item_description" id="item_description" class="form-control" value="{{ old('item_description', $budget->item_description) }}" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="effective_date" class="form-label fw-bold">Effective Date</label>
                                <input type="date" name="effective_date" id="effective_date" class="form-control" value="{{ old('effective_date', \Carbon\Carbon::parse($budget->effective_date)->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="budget" class="form-label fw-bold">Budget Value</label>
                                <input type="number" step="0.01" name="budget" id="budget" class="form-control" value="{{ old('budget', $budget->budget) }}" required>
                            </div>
                        </div>

                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <a href="{{ route('budget.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> Batal
                        </a>
                        <button type="submit" class="btn btn-warning shadow-sm">
                            <i class="fas fa-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection