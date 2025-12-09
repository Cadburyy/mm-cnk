@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Weights List</h2>
        <a class="btn btn-success" href="{{ route('weights.create') }}">
            <i class="fa fa-plus"></i> Create New Weight
        </a>
    </div>

    @if ($message = Session::get('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <p class="mb-0">{{ $message }}</p>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="card shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Material</th>
                            <th>Part</th>
                            <th>Weight (g)</th>
                            <th width="280px" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($weights as $weight)
                        <tr>
                            <td class="ps-4">{{ $weight->material }}</td>
                            <td>{{ $weight->part }}</td>
                            <td>{{ $weight->weight }}</td>
                            <td class="text-center">
                                <form action="{{ route('weights.destroy',$weight->id) }}" method="POST">
                                    <a class="btn btn-primary btn-sm me-1" href="{{ route('weights.edit',$weight->id) }}">
                                        <i class="fa fa-pen"></i> Edit
                                    </a>
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">
                                        <i class="fa fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="mt-3">
        {!! $weights->links() !!}
    </div>
</div>
@endsection