@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Edit Weight</h2>
        <a class="btn btn-secondary" href="{{ route('weights.index') }}">
            <i class="fa fa-arrow-left"></i> Back
        </a>
    </div>

    @if ($errors->any())
    <div class="alert alert-danger shadow-sm rounded-3">
        <strong>Whoops!</strong> There were some problems with your input.<br><br>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="card shadow-sm rounded-3 p-4">
        <form action="{{ route('weights.update', $weight->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="material" class="form-label"><strong>Material:</strong></label>
                        <input type="text" name="material" class="form-control text-uppercase" placeholder="e.g. METAL" value="{{ old('material', $weight->material) }}" required autocomplete='off' oninput="this.value = this.value.toUpperCase()">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="part" class="form-label"><strong>Part:</strong></label>
                        <input type="text" name="part" class="form-control text-uppercase" placeholder="e.g. BODY" value="{{ old('part', $weight->part) }}" required autocomplete='off' oninput="this.value = this.value.toUpperCase()">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="weight" class="form-label"><strong>Weight (Gram):</strong></label>
                        <input type="number" step="0.0001" name="weight" class="form-control" placeholder="0.0000" value="{{ old('weight', $weight->weight) }}" required autocomplete='off'>
                    </div>
                </div>

                <div class="col-12 text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fa-solid fa-floppy-disk me-2"></i> Update
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection