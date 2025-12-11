@extends('layouts.app')

@section('content')
@php
    $user = Auth::user();
@endphp

<style>
    body, html {
        overflow-x: hidden;
        overflow-y: auto;
    }

    .card-link-hover:hover .card {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.15) !important; 
        transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
    }

    .card-link-hover .card {
        transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
    }

    .card {
        border-radius: 1rem;
        border: 1px solid #e9ecef; 
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075)!important;
    }
</style>

<div class="container d-flex flex-column justify-content-center py-5">

    <h2 class="text-center mb-1">
        Welcome, {{ $user->name }} 
        <span role="img" aria-label="wave">👋</span>
    </h2>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-2 g-4 justify-content-center mt-3 mb-5">
        <div class="col">
            <a href="{{ route('items.index') }}" class="text-decoration-none card-link-hover">
                <div class="card h-100 text-center shadow-sm p-3">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center">
                        <i class="fas fa-industry fa-3x mb-3 text-primary"></i>
                        <h5 class="card-title"><strong>Produksi</strong></h5>
                        <p class="card-text text-muted small">Input hasil produksi (GKG, Scrap, Cakalan).</p>
                    </div>
                </div>
            </a>
        </div>
        
        <div class="col">
            <a href="{{ route('mutations.index') }}" class="text-decoration-none card-link-hover">
                <div class="card h-100 text-center shadow-sm p-3">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center">
                        <i class="fas fa-exchange-alt fa-3x mb-3 text-info"></i>
                        <h5 class="card-title"><strong>Mutasi Barang</strong></h5>
                        <p class="card-text text-muted small">Pindahkan stok produksi ke proses lain.</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col">
            <a href="{{ route('sales.index') }}" class="text-decoration-none card-link-hover">
                <div class="card h-100 text-center shadow-sm p-3">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center">
                        <i class="fas fa-cash-register fa-3x mb-3 text-success"></i>
                        <h5 class="card-title"><strong>Penjualan</strong></h5>
                        <p class="card-text text-muted small">Input penjualan ke customer.</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col">
            <a href="{{ route('weights.index') }}" class="text-decoration-none card-link-hover">
                <div class="card h-100 text-center shadow-sm p-3">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center">
                        <i class="fas fa-balance-scale fa-3x mb-3 text-warning"></i>
                        <h5 class="card-title"><strong>Master Berat</strong></h5>
                        <p class="card-text text-muted small">Kelola standar berat item/part.</p>
                    </div>
                </div>
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success text-center mt-5" role="alert">
            {{ session('status') }}
        </div>
    @endif
</div>
@endsection